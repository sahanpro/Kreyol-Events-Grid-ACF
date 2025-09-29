<?php
if(!defined('ABSPATH')) exit;

function keg_render_events_shortcode($atts){
    $atts = shortcode_atts([
        'per_page'  => 12,
        'post_type' => '',
    ], $atts, 'kreyol_events');

    $ptype = keg_detect_event_post_type( sanitize_key($atts['post_type']) );

    // current filter values
    $q      = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    $cat    = isset($_GET['event_category']) ? sanitize_text_field($_GET['event_category']) : '';
    $city   = isset($_GET['event_city']) ? sanitize_text_field($_GET['event_city']) : '';
    $mode   = isset($_GET['event_mode']) ? sanitize_text_field($_GET['event_mode']) : '';
    $preset = isset($_GET['date_preset']) ? sanitize_text_field($_GET['date_preset']) : '';
    $dfrom  = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $dto    = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';

    // dropdown data
    $cats  = keg_distinct_meta_values($ptype,'event_category',200);
    $default_cats = array('Music','Dancing','Sport and Fitness','Art & Culture','Social Activities','Restaurant');
    if(!empty($default_cats)){
        $default_cats = array_values(array_filter(array_map(function($dc){
            $dc = trim((string)$dc);
            return $dc !== '' ? $dc : null;
        }, $default_cats)));
        if(!empty($default_cats)){
            $cats = array_values(array_unique(array_merge($default_cats, $cats)));
        }
    }
    $cities= keg_distinct_meta_values($ptype,'event_city',200);

    wp_enqueue_style('keg-style');
    wp_enqueue_script('keg-search');

    ob_start(); ?>
    <div class="keg-wrap" id="events">
        <form id="keg-filters" class="keg-filters-card" method="get" action="">
        <div class="keg-row">
            <div class="keg-col">
                <label class="keg-label"><?php _e('Event Name','kreyol-events-grid-acf'); ?></label>
                <input type="text" id="keg-q" name="q" value="<?php echo esc_attr($q); ?>" placeholder="<?php esc_attr_e('Event name, artist, venue...','kreyol-events-grid-acf'); ?>" />
            </div>
            <div class="keg-col">
                <label class="keg-label"><?php _e('Category','kreyol-events-grid-acf'); ?></label>
                <select id="keg-ecat" name="event_category">
                    <option value=""><?php _e('All Categories','kreyol-events-grid-acf'); ?></option>
                    <?php foreach($cats as $v){ printf('<option value="%s"%s>%s</option>', esc_attr($v), selected($cat,$v,false), esc_html($v)); } ?>
                </select>
            </div>
        </div>

        <div class="keg-row">
            <div class="keg-col">
                <label class="keg-label"><?php _e('Location','kreyol-events-grid-acf'); ?></label>
                <input type="text" id="keg-ecity" name="event_city" value="<?php echo esc_attr($city); ?>" placeholder="<?php esc_attr_e('Enter city or location...','kreyol-events-grid-acf'); ?>" />
                <button type="button" id="keg-use-location" class="keg-btn ghost icon-left"><span class="keg-icn">📍</span><?php _e('Use Current Location','kreyol-events-grid-acf'); ?></button>
                <div id="keg-geo-status" class="keg-geo-status" aria-live="polite"></div>
            </div>
            <div class="keg-col">
                <label class="keg-label"><?php _e('Event Mode','kreyol-events-grid-acf'); ?></label>
                <select id="keg-mode" name="event_mode">
                    <?php $modes=array(''=>__('All Events','kreyol-events-grid-acf'),'Physical'=>__('Physical','kreyol-events-grid-acf'),'Online'=>__('Online','kreyol-events-grid-acf'));
                    foreach($modes as $val=>$lab){ printf('<option value="%s"%s>%s</option>', esc_attr($val), selected($mode,$val,false), esc_html($lab)); } ?>
                </select>
            </div>
        </div>

        <div class="keg-row kg-date-row">
            <div class="keg-col full">
                <label class="keg-label"><?php _e('Event Date','kreyol-events-grid-acf'); ?></label>
                <div class="keg-date-tabs" role="tablist">
                    <button type="button" class="keg-tab<?php echo $preset===''?' active':''; ?>" data-preset=""><?php _e('Any date','kreyol-events-grid-acf'); ?></button>
                    <button type="button" class="keg-tab<?php echo $preset==='today'?' active':''; ?>" data-preset="today"><?php _e('Today','kreyol-events-grid-acf'); ?></button>
                    <button type="button" class="keg-tab<?php echo $preset==='tomorrow'?' active':''; ?>" data-preset="tomorrow"><?php _e('Tomorrow','kreyol-events-grid-acf'); ?></button>
                    <button type="button" class="keg-tab<?php echo $preset==='weekend'?' active':''; ?>" data-preset="weekend"><?php _e('This weekend','kreyol-events-grid-acf'); ?></button>
                    <button type="button" class="keg-tab<?php echo $preset==='custom'?' active':''; ?>" data-preset="custom"><?php _e('Pick dates','kreyol-events-grid-acf'); ?></button>
                </div>
                <input type="hidden" name="date_preset" id="keg-date-preset" value="<?php echo esc_attr($preset); ?>" />
                <div id="keg-date-picker-wrap" class="keg-date-picker">
                    <div class="keg-col two">
                        <label class="keg-sublabel"><?php _e('From','kreyol-events-grid-acf'); ?></label>
                        <input type="date" id="keg-date-from" name="date_from" value="<?php echo esc_attr($dfrom); ?>" placeholder="yyyy-mm-dd" />
                    </div>
                    <div class="keg-col two">
                        <label class="keg-sublabel"><?php _e('To','kreyol-events-grid-acf'); ?></label>
                        <input type="date" id="keg-date-to" name="date_to" value="<?php echo esc_attr($dto); ?>" placeholder="yyyy-mm-dd" />
                    </div>
                </div>
            </div>
        </div>

        <div class="keg-actions">
            <button class="keg-btn primary" type="submit"><span class="dot"></span><?php _e('Search Events','kreyol-events-grid-acf'); ?></button>
            <button class="keg-btn ghost" type="button" id="keg-clear"><?php _e('Clear Filters','kreyol-events-grid-acf'); ?></button>
        </div>
    </form>

        <div class="keg-meta-bar">
            <?php echo keg_render_results_count($ptype); ?>
        </div>

        <div id="keg-grid" class="keg-grid" aria-live="polite">
            <?php echo keg_events_loop_html(['per_page'=>(int)$atts['per_page'],'post_type'=>$ptype,'q'=>$q]); ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('kreyol_events','keg_render_events_shortcode');

function keg_render_results_count($ptype){
    // cheap count query (no pagination) to show "X results"
    $meta_query=['relation'=>'AND'];
    $keyword = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
    if($keyword){
        $meta_query[]=['relation'=>'OR',
            ['key'=>'event_name','value'=>$keyword,'compare'=>'LIKE'],
            ['key'=>'event_city','value'=>$keyword,'compare'=>'LIKE'],
            ['key'=>'event_category','value'=>$keyword,'compare'=>'LIKE'],
        ];
    }
    if(!empty($_GET['event_category'])) $meta_query[]=['key'=>'event_category','value'=>sanitize_text_field($_GET['event_category']),'compare'=>'LIKE'];
    if(!empty($_GET['event_city'])){
        $city_val = sanitize_text_field($_GET['event_city']);
        $location_clause = keg_build_location_clause($city_val);
        if(!empty($location_clause)){
            $meta_query[] = $location_clause;
        }else{
            $meta_query[] = ['key'=>'event_city','value'=>$city_val,'compare'=>'LIKE'];
        }
    }
    if(!empty($_GET['event_mode']))     $meta_query[]=['key'=>'event_mode','value'=>sanitize_text_field($_GET['event_mode']),'compare'=>'='];

    $preset = isset($_GET['date_preset']) ? sanitize_text_field($_GET['date_preset']) : '';
    $dfrom  = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $dto    = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $allowed_presets = array('','today','tomorrow','weekend','custom');
    if(!in_array($preset,$allowed_presets,true)){ $preset=''; }

    list($from_ts,$to_ts) = keg_resolve_requested_date_range($preset,$dfrom,$dto);
    if($from_ts || $to_ts){
        $date_clause = keg_date_meta_for_range($from_ts,$to_ts);
        if(!empty($date_clause)){ $meta_query[] = $date_clause; }
    }

    $args=['post_type'=>$ptype,'post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','no_found_rows'=>false,'meta_query'=>$meta_query];
    $q=new WP_Query($args);
    $total = intval($q->found_posts);
    wp_reset_postdata();
    return '<div class="keg-count">'. sprintf( _n('%s result','%s results',$total,'kreyol-events-grid-acf'), number_format_i18n($total) ) .'</div>';
}

/** Main loop that builds the actual results with pagination */
function keg_events_loop_html($args=[]){
    $per_page = isset($args['per_page']) ? (int)$args['per_page'] : 12;
    $ptype    = isset($args['post_type']) ? $args['post_type'] : 'post';
    $paged    = max(1, get_query_var('paged') ? get_query_var('paged') : ( get_query_var('page') ? get_query_var('page') : (isset($_GET['pg']) ? intval($_GET['pg']) : 1 ) ));
    $keyword  = isset($args['q']) ? sanitize_text_field($args['q']) : '';

    $meta_query=['relation'=>'AND'];
    $tax_query=[];

    $preset = isset($_GET['date_preset']) ? sanitize_text_field($_GET['date_preset']) : '';
    $dfrom  = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
    $dto    = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
    $allowed_presets = array('','today','tomorrow','weekend','custom');
    if(!in_array($preset,$allowed_presets,true)){ $preset=''; }

    if($keyword){
        $meta_query[]=['relation'=>'OR',
            ['key'=>'event_name','value'=>$keyword,'compare'=>'LIKE'],
            ['key'=>'event_city','value'=>$keyword,'compare'=>'LIKE'],
            ['key'=>'event_category','value'=>$keyword,'compare'=>'LIKE'],
        ];
    }
    if(!empty($_GET['event_category'])){
        $val = sanitize_text_field($_GET['event_category']);
        $meta_query[]=['key'=>'event_category','value'=>$val,'compare'=>'LIKE'];
    }
    if(!empty($_GET['event_city'])){
        $val = sanitize_text_field($_GET['event_city']);
        $location_clause = keg_build_location_clause($val);
        if(!empty($location_clause)){
            $meta_query[] = $location_clause;
        }else{
            $meta_query[]=['key'=>'event_city','value'=>$val,'compare'=>'LIKE'];
        }
    }
    if(!empty($_GET['event_mode'])){
        $val = sanitize_text_field($_GET['event_mode']);
        $meta_query[]=['key'=>'event_mode','value'=>$val,'compare'=>'='];
    }

    list($from_ts,$to_ts) = keg_resolve_requested_date_range($preset,$dfrom,$dto);
    if($from_ts || $to_ts){
        $date_clause = keg_date_meta_for_range($from_ts,$to_ts);
        if(!empty($date_clause)){ $meta_query[] = $date_clause; }
    }

    $query_args=[

        'post_type'=>$ptype,
        'posts_per_page'=>$per_page,
        'post_status'=>'publish',
        'paged'=>$paged,
        'meta_query'=>$meta_query,
        'tax_query'=>$tax_query,
        '_keg_title_like'=>$keyword,
        '_keg_sort_events'=>1,
    ];

    // sort by coalesced date (ACF event_date numeric or TEC _EventStartDate datetime)
    add_filter('posts_clauses', 'keg_events_sort_clauses', 10, 2);

    if($keyword){ add_filter('posts_where','keg_where_title_like',10,2); }

    $q=new WP_Query($query_args);

    if($keyword){ remove_filter('posts_where','keg_where_title_like',10); }
    remove_filter('posts_clauses', 'keg_events_sort_clauses', 10);

    ob_start();
    if($q->have_posts()){
        echo '<div class="keg-cards">';
        while($q->have_posts()){ $q->the_post(); include KEG_PATH.'templates/card.php'; }
        echo '</div>';

        // pagination preserve filters
        $big=999999999;
        $base=str_replace($big,'%#%',esc_url(get_pagenum_link($big)));
        $paginate=paginate_links([
            'base'=>$base,
            'format'=>'?pg=%#%',
            'current'=>max(1,$paged),
            'total'=>$q->max_num_pages,
            'prev_text'=>__('« Prev','kreyol-events-grid-acf'),
            'next_text'=>__('Next »','kreyol-events-grid-acf'),
            'type'=>'list',
        ]);
        if($paginate){
            $qs=$_GET; unset($qs['pg']);
            $paginate=preg_replace_callback('/href="([^"]+)"/', function($m) use($qs){
                $url=$m[1]; $sep=(strpos($url,'?')!==false)?'&':'?';
                if(!empty($qs)) $url .= $sep . http_build_query($qs);
                return 'href="'.esc_url($url).'#events"';
            }, $paginate);
            echo '<nav class="keg-pagination">'.$paginate.'</nav>';
        }
        wp_reset_postdata();
    }else{
        echo '<p class="keg-empty">'.esc_html__('No events found. Try different filters.','kreyol-events-grid-acf').'</p>';
    }
    return ob_get_clean();
}
