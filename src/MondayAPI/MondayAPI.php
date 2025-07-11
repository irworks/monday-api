<?php

namespace TBlack\MondayAPI;

class MondayAPI
{
    private $APIV2_Token;
    private $API_Url     = "https://api.monday.com/v2/";
    private $debug       = false;

    protected $version = '2025-01';

    const TYPE_QUERY    = 'query';
    const TYPE_MUTAT    = 'mutation';

    function __construct( Bool $debug = false )
    {
        $this->debug = $debug;
    }

    private function printDebug($print)
    {
        echo '<div style="background: #f9f9f9; padding: 20px; position: relative; border: solid 1px #dedede;">
        '.$print.'
        </div>';
    }

    public function setToken( Token $token )
    {
        $this->APIV2_Token = $token;
        return $this;
    }

    private function content($type, $request)
    {
        if($this->debug){
            $this->printDebug( $type.' { '.$request.' } ' );
        }
        return json_encode(['query' => $type.' { '.$request.' } ']);
    }

    protected function request( $type = self::TYPE_QUERY, $request = null )
    {
        $headers = [
            'Content-Type: application/json',
            'User-Agent: [Tblack-IT] GraphQL Client',
            'Authorization: ' . $this->APIV2_Token->getToken(),
            'API-Version: ' . $this->version,
        ];

        $data = @file_get_contents($this->API_Url, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => $headers,
                'content' => $this->content($type, str_replace(["\r", "\n"], '', $request)),
            ]
        ]));

        return $this->response( $data );
    }

    protected function response( $data )
    {
        if(!$data)
            return false;

        $json = json_decode($data, true);

        if( isset($json['data']) ){
            return $json['data'];
        }else if( isset($json['errors']) && is_array($json['errors']) ){
            return $json['errors'];
        }

        return false;
    }
}

?>
