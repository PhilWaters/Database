# Database

Simple database interface with row iterator.

## Examples

### Instantiation

```
$database = new Database($connection);
```

### Query

To select a row from `table`.

```
$query =
    "SELECT col1
       FROM table
      WHERE id = :col2_value";
$params = array(
    "id" => 123
);
$rows = $database->query($query);
```

To insert a row into `table`.

```
$query =
    "INSERT INTO table
               ( col1 )
        VALUES ( :col1_value,
                 :col2_value )";
$params = array(
    "col1_value" => "foo",
    "col2_value" => "bar"
);
$database->query($query, $params);
```

### Truncating Tables

To truncate `table`.

```
$database->truncate("table");
```

### Transactions

Using database transactions.

```
$database->beginTransaction();
$database->query(...);

if (isOK()) {
    $database->commit();
} else {
    $database->rollback();
}
```

### Renaming Tables

Renaming `old_name` to `new_name`.

```
$database->renameTable("old_name", "new_name");
```

### Swapping Tables

Swapping `table_working` with `table_live`. This can be useful when updating a working copy of a table then once complete swap it with the live table.

```
$database->swapTables("table_working", "table_live");
```

### Iterating Rows

To iterate all selected rows using `foreach`.

```
$query =
    "SELECT a, b, c
       FROM table";
$rows = $database->query($query);

foreach ($rows as $row) {
    echo $row['a'];
}
```

### Get All Rows

To get all rows as an array.

```
$rows = $database->query($query);

echo json_encode($rows->all);
```

### Row Count

To get the number of rows selected.

```
$rows = $database->query($query);

echo count($rows);
```

### Array Access

To access a specific row.

```
$rows = $database->query($query);

echo $row[7]['col'];
```
