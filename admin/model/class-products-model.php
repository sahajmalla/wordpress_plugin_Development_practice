<?php

/**
 * Communicates to database for woo commerce products
 */
class Products_Model
{
    private $query;
    private $posts_per_page;
    private $searchVal;

    public function __construct()
    {
        $this->posts_per_page  = get_option('posts_per_page');
        $this->searchVal =   isset($_GET['s']) ? $_GET['s'] : '';
    }
    /**
     * gets the products from woo commerce
     *
     * @return Array
     */
    public function get()
    {
        $products = [];

        if (isset($_GET['product_cat']) && isset($_GET['filter_action'])) {
            $categoryVal = $_GET['product_cat'];
            
        } else {
            $categoryVal = '';
        }
       
        $this->query = new WP_Query(
            [
                'post_type' => ['product'],
                'posts_per_page'  => -1,
                'product_cat' =>$categoryVal,
                'meta_query' =>
                [
                    'key' => '_sku',
                    'value' => $this->searchVal,
                    'compare' => "LIKE"
                ],
                
                's' => $this->searchVal,
            ]
        );


        while ($this->query->have_posts()) : $this->query->the_post();
            global $product;
            array_push($products, $product);

        endwhile;

        wp_reset_postdata();
        return $products;
    }

    /**
     * Returns the query
     *
     * @return void
     */
    public function get_query()
    {
        var_dump($this->query);
        return $this->query;
    }


    /**
     * Pagination for the product
     *
     * @return void
     */
    public function pagination()
    {
        $paged = isset($_GET['paged']) ? (int) $_GET['paged'] : 1;
        $items_per_page = $this->posts_per_page;
        $page_path =  'admin.php?page=showproducts';

        $query = new WP_Query(
            [
                'post_type' => 'product',
                'posts_per_page'  => $items_per_page,
                'paged' => $paged,
                's' => $this->searchVal
            ]
        );

        if (empty($query))
            return 'Query must be set';

        $total_pages = (int) $query->max_num_pages;
        //as get_query_var() won't work in admin end


        $nextpage = $paged + 1;
        $prevpage = max(($paged - 1), 0); //max() to discard any negative value

        //assumed, we're using the default posts_per_page value in our query too
        $items_per_page = empty($items_per_page) ? (int) get_option('posts_per_page') : (int) $items_per_page;
        $lastpage = ceil($query->found_posts / $items_per_page);

        if (empty($page_path))
            return _e('Admin path is not set');

        //adding 'paged' parameter to page_path
        $next_page_path = add_query_arg('paged', $nextpage, $page_path);
        $prev_page_path = add_query_arg('paged', $prevpage, $page_path);
        $lastpage_path  = add_query_arg('paged', $lastpage, $page_path);

        echo '<div class="tablenav bottom">';
        echo '<div class="alignleft">';
        //Display the 'Previous' buttons
        if ($prevpage !== 0) {
            echo '<a class="button button-default" title="First page" href="' . $page_path . '">&laquo; <span class="screen-reader-text">First page</span></a> ';
            echo '<a class="button button-primary" title="Previous page" href="' . $prev_page_path . '">&lsaquo; <span class="screen-reader-text">Previous page</span></a> ';
        }

        //Display current page number
        if ($paged !== 1 && $paged !== $total_pages) {
            echo '<span class="screen-reader-text">Current Page</span>';
            echo '<span id="this-page">' . $paged . '</span>';
        }

        //Display the 'Next' buttons
        if ($total_pages > $paged) {
            echo ' <a class="button button-primary" title="Next page" href="' . $next_page_path . '"><span class="screen-reader-text">Next page</span> &rsaquo;</a>';
            echo ' <a class="button button-default" title="Last page" href="' . $lastpage_path . '"><span class="screen-reader-text">Last page</span> &raquo;</a>';
        }
        echo '</div>';
        echo '</div>';
    }

    /**
     * Gets the variation of the product item passed
     *
     * @param mixed $item
     * @return Array
     */
    public function get_Variations($item)
    {
        if ($item->is_type('variable')) {
            return $item->get_variation_attributes();
        }
    }
}
