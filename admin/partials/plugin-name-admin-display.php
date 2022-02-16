<?php
require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
require_once plugin_dir_path(dirname(__FILE__)) . 'model\class-products-model.php';

/**
 * Lists the product using wordpress list table
 */

class ProductsListTable extends WP_List_Table
{
    /**
     * Add extra markup in the toolbars before or after the list
     * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
     */
    public function extra_tablenav($which)
    {

?>

        <form action="">
            <?= wc_product_dropdown_categories() ?>
            <input type="hidden" name="page" value="showproducts" />
            <input type='submit' name='filter_action' id='post-query-submit' class='button' value='Filter'>
        </form>
    <?php


    }

    /**
     * Gets all the product from woo commerce, stores it and returns custom data
     *
     * @return Array
     */
    public function get_items()
    {
        $data = [];

        $products = new Products_Model;

        $prod_array = [];

        foreach ($products->get() as $item) {

            $image = wp_get_attachment_image_src(get_post_thumbnail_id($item->id), 'single-post-thumbnail');


            $prod_array['image']  = $image[0];


            $prod_array['id'] = $item->get_id();
            $prod_array['name'] = $item->get_name();
            $prod_array['sku'] =  $item->get_sku();
            $prod_array['category'] = $item->get_categories();
            $prod_array['price'] = $item->get_price();
            $prod_array['variation'] = $products->get_Variations($item);

            array_push($data, $prod_array);
        }

        return $data;
    }

    /**
     * Prepare the items for the table to process
     *
     * @return void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();

        $hidden = $this->get_hidden_columns();

        $sortable = $this->get_sortable_columns();


        $data = $this->get_items();
        usort($data, array(&$this, 'sort_data'));

        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);

        $this->set_pagination_args(array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ));

        $data = array_slice($data, (($currentPage - 1) * $perPage), $perPage);

        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }

    /**
     *  Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {

        $columns = [
            'image' => 'Image',
            'name' => 'Name',
            'sku' => 'SKU',
            'category' => 'Category',
            'variation' => 'Variation',
            'price' => 'Price'
        ];

        return $columns;
    }

    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }

    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return [
            'price' => [
                'price',
                false
            ]
        ];
    }

    /**
     * Returns value corresponding to the item
     *
     * @param [type] $item
     * @param [type] $column_name
     * @return mixed
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'image':
                return  empty($item[$column_name]) ?
                    'No image' :
                    "<img width=80 height=80 src= '$item[$column_name]' class='attachment-thumbnail size-thumbnail'";

            case 'name':
                return empty($item[$column_name]) ? 'No Name' : $item[$column_name];
            case 'sku':
                return empty($item[$column_name]) ? 'No Sku' : $item[$column_name];
            case 'category':
                return $item[$column_name];
            case 'variation':
                $variation = [];
                $variation_in_strings = '';
                if (empty($item[$column_name])) {
                    $url = get_admin_url();
                    $item_id = $item['id'];
                    $message = "No variation found.<br>Do you want to add variation?<br><a href=$url/post.php?post=$item_id&action=edit>Click here</a>";

                    return $message;
                } else {

                    foreach ($item[$column_name] as $key => $value) {
                        if (function_exists('wc_attribute_label')) {
                            $variation[wc_attribute_label($key)] = $value;
                        }
                    }

                    foreach ($variation as $key => $value) {

                        if ($variation_in_strings === '') {
                            $variation_in_strings =  ('<strong>' . $key . '</strong>' . ': ' . implode(', ', $value) . '<br>');
                        } else {
                            $variation_in_strings .=  ('<strong>' . $key . '</strong>' . ': ' . implode(', ', $value) . '<br>');
                        }
                    }
                    return $variation_in_strings;
                }
                return;
            case 'price':
                return $item[$column_name];
        }
    }

    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data($a, $b)
    {
        // Set defaults
        $orderby = 'price';
        $order = 'asc';

        // If orderby is set, use this as the sort column
        if (!empty($_GET['orderby'])) {
            $orderby = $_GET['orderby'];
        }

        // If order is set use this as the order
        if (!empty($_GET['order'])) {
            $order = $_GET['order'];
        }


        $result = strcmp($a[$orderby], $b[$orderby]);

        if ($order === 'asc') {
            return $result;
        }

        return -$result;
    }
}

/**
 * Function to list the table
 *
 * @return void
 */
function show_list_table()
{
    $productsListTable = new  ProductsListTable();

    $productsListTable->prepare_items();


    ?>
    <div class="wrap">
        <div id="icon-users" class="icon32"></div>
        <h2>Woo Commerce Product List</h2>


        <form>
            <input type="hidden" name="page" value="showproducts" />
            <?= $productsListTable->search_box('search', 'search_id'); ?>
        </form>

        <?= $productsListTable->display(); ?>
    </div>
<?php

}

show_list_table();

?>