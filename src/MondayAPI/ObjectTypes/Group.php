<?php

namespace TBlack\MondayAPI\ObjectTypes;

class Group extends ObjectModel
{
    // Query scope
    static $scope = 'groups';

    // Arguments
    static $arguments = array();

    static array $createItemArguments = [
        'board_id' => '!Int',
        'group_name' => '!String',
    ];

    // Fields
    static $fields = [
        'id' => ['type' => '!ID'],
        'items' => ['type' => '[Item]', 'object' => 'Item'],
        'position' => ['type' => '!String'],
        'title' => ['type' => '!String']
    ];

}

?>
