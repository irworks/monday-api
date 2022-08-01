<?php

namespace TBlack\MondayAPI;

use TBlack\MondayAPI\ObjectTypes\Group;
use TBlack\MondayAPI\Querying\Query;
use TBlack\MondayAPI\ObjectTypes\Item;
use TBlack\MondayAPI\ObjectTypes\SubItem;
use TBlack\MondayAPI\ObjectTypes\Board;
use TBlack\MondayAPI\ObjectTypes\Column;
use TBlack\MondayAPI\ObjectTypes\BoardKind;

class MondayBoard extends MondayAPI
{
    protected $board_id = false;
    protected $group_id = false;

    public function on(int $board_id)
    {
        $this->board_id = $board_id;
        return $this;
    }

    public function group(string $group_id)
    {
        $this->group_id = $group_id;
        return $this;
    }

    /**
     * Create a new monday board and return its id on success.
     * @param string $board_name
     * @param string $board_kind
     * @param array $optionals
     * @return int|bool
     */
    public function create(string $board_name, string $board_kind = BoardKind::PRV, array $optionals = []): int|bool
    {
        $Board = new Board();

        $arguments = array_merge(['board_name' => $board_name], $optionals);

        $create = Query::create(
            'create_board',
            $Board->getArguments($arguments, Board::$create_item_arguments, ' board_kind:' . $board_kind . ', '),
            $Board->getFields(['id'])
        );

        return $this->request(self::TYPE_MUTAT, $create)['create_board']['id'] ?? false;
    }

    public function archiveBoard(array $fields = [])
    {
        $Board = new Board();

        $arguments = [
            'board_id' => $this->board_id,
        ];

        $create = Query::create(
            'archive_board',
            $Board->getArguments($arguments, Board::$archive_arguments),
            $Board->getFields($fields)
        );

        return $this->request(self::TYPE_MUTAT, $create);
    }

    public function getBoards(array $arguments = [], array $fields = []): array
    {
        $Board = new Board();

        if ($this->board_id !== false && !isset($arguments['ids'])) {
            $arguments['ids'] = $this->board_id;
        }

        $boards = Query::create(
            Board::$scope,
            $Board->getArguments($arguments),
            $Board->getFields($fields)
        );

        return $this->request(self::TYPE_QUERY, $boards)['boards']?? [];
    }

    public function getColumns(array $fields = [])
    {
        $Column = new Column();
        $Board = new Board();

        $columns = Query::create(
            Column::$scope,
            '',
            $Column->getFields($fields)
        );

        $boards = Query::create(
            Board::$scope,
            $Board->getArguments(['ids' => $this->board_id]),
            [$columns]
        );

        return $this->request(self::TYPE_QUERY, $boards);
    }

    /**
     * Create a group and return its id on success.
     * @param string $name
     * @return string|bool
     */
    public function createGroup(string $name): string|bool
    {
        $group = new Group();
        if (empty($this->board_id)) {
            return false;
        }

        $arguments = ['group_name' => $name, 'board_id' => $this->board_id];

        $create = Query::create(
            'create_group',
            $group->getArguments($arguments, Group::$createItemArguments),
            $group->getFields(['id'])
        );

        return $this->request(self::TYPE_MUTAT, $create)['create_group']['id'] ?? false;
    }

    public function createColumn(string $name, string $type = 'text', string $description = ''): string|bool
    {
        if (empty($this->board_id)) {
            return false;
        }

        $arguments = [
            'title' => $name, 'description' => $description,
            'board_id' => $this->board_id
        ];

        $create = Query::create(
            'create_column',
            Query::buildArguments($arguments, ' column_type:' . $type . ', '),
            ['id']
        );

        return $this->request(self::TYPE_MUTAT, $create)['create_column']['id'] ?? false;
    }


    public function getItems(array $arguments = [], array $fields = []): array
    {
        $Item = new Item();

        $items = Query::create(
            Item::$scope,
            $Item->getArguments($arguments),
            $Item->getFields($fields)
        );

        foreach ($fields as $field => $value) {
            if (!is_array($value)) {
                continue;
            }

            $items = str_replace($field, $field . ' {' . implode(' ', $value) . '}', $items);
        }

        return $this->request(self::TYPE_QUERY, $items)['items'] ?? [];
    }

    public function addItem(string $item_name, array $itens = [], $create_labels_if_missing = false): int|false
    {
        if (!$this->board_id || !$this->group_id)
            return -1;

        $arguments = [
            'board_id' => $this->board_id,
            'group_id' => $this->group_id,
            'item_name' => addslashes($item_name),
            'column_values' => Column::newColumnValues($itens),
        ];

        $Item = new Item();

        $create = Query::create(
            'create_item',
            $Item->getArguments($arguments, Item::$create_item_arguments),
            $Item->getFields(['id'])
        );

        if ($create_labels_if_missing) {
            $create = str_replace('}"){', '}", create_labels_if_missing:true){', $create);
        }

        return $this->request(self::TYPE_MUTAT, $create)['create_item']['id'] ?? false;
    }

    public function addSubItem(int $parent_item_id, string $item_name, array $itens = [], $create_labels_if_missing = false)
    {
        $arguments = [
            'parent_item_id' => $parent_item_id,
            'item_name' => $item_name,
            'column_values' => Column::newColumnValues($itens),
        ];

        $SubItem = new SubItem();

        $create = Query::create(
            'create_subitem',
            $SubItem->getArguments($arguments, SubItem::$create_item_arguments),
            $SubItem->getFields(['id'])
        );

        if ($create_labels_if_missing)
            $create = str_replace('}"){', '}", create_labels_if_missing:true){', $create);

        return $this->request(self::TYPE_MUTAT, $create);
    }

    public function archiveItem(int $item_id)
    {
        $Item = new Item();

        $archive = Query::create(
            'archive_item',
            $Item->getArguments(['item_id' => $item_id], Item::$archive_delete_arguments),
            $Item->getFields(['id'])
        );

        return $this->request(self::TYPE_MUTAT, $archive);
    }

    public function deleteItem(int $item_id)
    {
        $Item = new Item();

        $delete = Query::create(
            'delete_item',
            $Item->getArguments(['item_id' => $item_id], Item::$archive_delete_arguments),
            $Item->getFields(['id'])
        );

        return $this->request(self::TYPE_MUTAT, $delete);
    }

    public function changeMultipleColumnValues(int $item_id, array $column_values = []): int|false
    {
        if (!$this->board_id || !$this->group_id)
            return -1;

        $arguments = [
            'board_id' => $this->board_id,
            'item_id' => $item_id,
            'column_values' => Column::newColumnValues($column_values),
        ];

        $Item = new Item();

        $create = Query::create(
            'change_multiple_column_values',
            $Item->getArguments($arguments, Item::$change_multiple_column_values),
            $Item->getFields(['id'])
        );

        return $this->request(self::TYPE_MUTAT, $create)['change_multiple_column_values']['id'] ?? false;
    }

    public function customQuery($query)
    {
        return $this->request(self::TYPE_QUERY, $query);
    }

    public function customMutation($query)
    {
        return $this->request(self::TYPE_MUTAT, $query);
    }
}


?>
