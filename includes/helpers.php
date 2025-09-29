<?php
if(!defined('ABSPATH')) exit;

/** Resolve an existing events post type without registering a new one */
function keg_detect_event_post_type($override=''){
    if($override && post_type_exists($override)) return $override;
    if(post_type_exists('tribe_events')) return 'tribe_events';
    if(post_type_exists('event')) return 'event';
    if(post_type_exists('events')) return 'events';
    if(post_type_exists('kreyol_event')) return 'kreyol_event';
    return 'post';
}

/** Distinct list of values for a meta key (to populate dropdowns) */
function keg_distinct_meta_values($post_type, $meta_key, $limit=200){
    global $wpdb;
    $post_type = sanitize_key($post_type);
    $meta_key  = sanitize_key($meta_key);
    $limit     = intval($limit);
    $sql = $wpdb->prepare("
        SELECT DISTINCT pm.meta_value
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
        WHERE p.post_type = %s AND p.post_status='publish' AND pm.meta_key = %s
        AND pm.meta_value <> '' AND pm.meta_value IS NOT NULL
        ORDER BY pm.meta_value ASC
        LIMIT $limit
    ", $post_type, $meta_key);
    $vals = $wpdb->get_col($sql);
    // sanitize distinct values
    $out = [];
    if($vals){
        foreach($vals as $v){
            $sv = is_string($v) ? trim(wp_strip_all_tags($v)) : $v;
            if($sv!=='' && !in_array($sv,$out,true)) $out[]=$sv;
        }
    }
    return $out;
}

/** Date preset helper */
function keg_date_preset_range($preset){
    $preset = trim(strtolower($preset));
    $now    = current_time('timestamp');
    $today_start = strtotime(date('Y-m-d 00:00:00',$now));
    $today_end   = strtotime(date('Y-m-d 23:59:59',$now));
    $tomorrow_start = strtotime('+1 day',$today_start);
    $tomorrow_end   = strtotime('+1 day',$today_end);
    if($preset==='today') return [$today_start,$today_end];
    if($preset==='tomorrow') return [$tomorrow_start,$tomorrow_end];
    if($preset==='weekend'){
        $w=(int)date('w',$now);
        if($w===6) return [$today_start,strtotime('+1 day',$today_end)];
        if($w===0) return [$today_start,$today_end];
        $days_to_sat=(6-$w+7)%7;
        $sat_start=strtotime('+'.$days_to_sat.' days',$today_start);
        $sun_end  =strtotime('+'.($days_to_sat+1).' days',$today_end);
        return [$sat_start,$sun_end];
    }
    return [null,null];
}

/** Title LIKE helper (used to search post title without top-level 's') */
function keg_where_title_like($where,$wp_query){
    global $wpdb;
    $needle = $wp_query->get('_keg_title_like');
    if(!empty($needle)){
        $like = '%' . $wpdb->esc_like($needle) . '%';
        $where .= $wpdb->prepare(" OR {$wpdb->posts}.post_title LIKE %s ", $like);
    }
    return $where;
}


/**
 * Build a meta_query OR block that matches possible event_date formats across a date window.
 * Supports: UNIX timestamp (NUMERIC), Ymd, "F j, Y", "d/m/Y", plus TEC _EventStartDate (DATETIME).
 */
if(!function_exists('keg_build_date_or')){
function keg_build_date_or($from_ts, $to_ts=null){
    $from_ts = intval($from_ts);
    $to_ts   = intval($to_ts ?: $from_ts);
    if($to_ts < $from_ts){ $to_ts = $from_ts; }
    // Cap to 180 days
    if(($to_ts - $from_ts) > 180*DAY_IN_SECONDS){
        $to_ts = $from_ts + 180*DAY_IN_SECONDS;
    }
    $or = array('relation'=>'OR');
    // Range for numeric timestamp and Tribe datetime
    $or[] = array('key'=>'event_date','value'=>$from_ts,'compare'=>'>=','type'=>'NUMERIC');
    $or[] = array('key'=>'_EventStartDate','value'=>date('Y-m-d H:i:s',$from_ts),'compare'=>'>=','type'=>'DATETIME');
    $or[] = array('key'=>'event_date','value'=>$to_ts,'compare'=>'<=','type'=>'NUMERIC');
    $or[] = array('key'=>'_EventStartDate','value'=>date('Y-m-d H:i:s',$to_ts),'compare'=>'<=','type'=>'DATETIME');
    // Equality for day strings
    for($t=$from_ts; $t<=$to_ts; $t+=DAY_IN_SECONDS){
        $or[] = array('key'=>'event_date','value'=>date('F j, Y',$t),'compare'=>'=');
        $or[] = array('key'=>'event_date','value'=>date('d/m/Y',$t),'compare'=>'=');
        $or[] = array('key'=>'event_date','value'=>date('Ymd',$t),'compare'=>'=');
    }
    return $or;
}}


/**
 * ---------- Kreyol Events: robust date helpers ----------
 */

/** Parse many formats into a midnight timestamp. */
if (!function_exists('keg_parse_to_ts')){
function keg_parse_to_ts($val){
    if(!$val){ return null; }
    // ACF Ymd numeric string
    if(is_numeric($val)){
        $s = (string)$val;
        if(strlen($s)===8){
            $Y = (int)substr($s,0,4);
            $m = (int)substr($s,4,2);
            $d = (int)substr($s,6,2);
            if(checkdate($m,$d,$Y)) return mktime(0,0,0,$m,$d,$Y);
        }
        // already a unix ts? we reject tiny numbers (avoid 1970)
        $ts = (int)$val;
        if($ts > 60*60*24*365) return $ts;
    }
    // Y-m-d
    if(preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $val, $m)){
        $Y=(int)$m[1]; $mo=(int)$m[2]; $d=(int)$m[3];
        if(checkdate($mo,$d,$Y)) return mktime(0,0,0,$mo,$d,$Y);
    }
    // d/m/Y
    if(preg_match('~^(\d{2})\/(\d{2})\/(\d{4})$~', $val, $m)){
        $d=(int)$m[1]; $mo=(int)$m[2]; $Y=(int)$m[3];
        if(checkdate($mo,$d,$Y)) return mktime(0,0,0,$mo,$d,$Y);
    }
    // "F j, Y" or any strtotime-able string
    $ts = strtotime($val);
    if($ts && $ts>0){
        return mktime(0,0,0,(int)date('m',$ts),(int)date('d',$ts),(int)date('Y',$ts));
    }
    return null;
}}

/** Build an OR block for date range supporting Ymd and legacy strings. */
if (!function_exists('keg_date_meta_for_range')){
function keg_date_meta_for_range($from_ts, $to_ts){
    if(!$from_ts && !$to_ts){ return array(); }
    if(!$from_ts){ $from_ts = $to_ts; }
    if(!$to_ts){ $to_ts = $from_ts; }
    if($to_ts < $from_ts){ $t=$from_ts; $from_ts=$to_ts; $to_ts=$t; }

    $from_ts = (int)$from_ts;
    $to_ts   = (int)$to_ts;
    $range_end_ts = $to_ts + DAY_IN_SECONDS - 1; // include entire final day

    $fromYmd = date('Ymd',$from_ts);
    $toYmd   = date('Ymd',$to_ts);
    $from_dt = date('Y-m-d 00:00:00',$from_ts);
    $to_dt   = date('Y-m-d 23:59:59',$to_ts);

    $or = array('relation'=>'OR');

    // Match ACF-style Ymd values stored as strings
    $or[] = array(
        'key'     => 'event_date',
        'value'   => array($fromYmd,$toYmd),
        'compare' => 'BETWEEN',
        'type'    => 'CHAR',
    );

    // Match unix timestamps saved as numeric meta
    $or[] = array(
        'key'     => 'event_date',
        'value'   => array($from_ts,$range_end_ts),
        'compare' => 'BETWEEN',
        'type'    => 'NUMERIC',
    );

    // Match The Events Calendar style datetime values
    $or[] = array(
        'key'     => '_EventStartDate',
        'value'   => array($from_dt,$to_dt),
        'compare' => 'BETWEEN',
        'type'    => 'DATETIME',
    );

    // Equality per day for common legacy string formats (cap 90 days)
    $max_days = 90;
    $days = min($max_days, max(1, (int)(($to_ts-$from_ts)/DAY_IN_SECONDS)+1));
    $t = $from_ts;
    for($i=0; $i<$days; $i++){
        $or[] = array('key'=>'event_date','value'=>date('F j, Y',$t),'compare'=>'=');
        $or[] = array('key'=>'event_date','value'=>date('Y-m-d',$t),'compare'=>'=');
        $or[] = array('key'=>'event_date','value'=>date('d/m/Y',$t),'compare'=>'=');
        $t += DAY_IN_SECONDS;
    }
    return $or;
}}

/** Resolve a date range from preset + custom inputs. */
if (!function_exists('keg_resolve_requested_date_range')){
function keg_resolve_requested_date_range($preset, $from_input, $to_input){
    $preset = trim(strtolower((string)$preset));

    if(in_array($preset, array('today','tomorrow','weekend'), true)){
        list($from_ts, $to_ts) = keg_date_preset_range($preset);
        return array($from_ts, $to_ts);
    }

    $from_ts = $from_input ? keg_parse_to_ts($from_input) : null;
    $to_ts   = $to_input ? keg_parse_to_ts($to_input) : null;

    if($from_ts && $to_ts && $to_ts < $from_ts){
        $tmp = $from_ts;
        $from_ts = $to_ts;
        $to_ts = $tmp;
    }

    if(!$from_ts && !$to_ts){
        return array(null,null);
    }

    if(!$from_ts){ $from_ts = $to_ts; }
    if(!$to_ts){   $to_ts   = $from_ts; }

    return array($from_ts, $to_ts);
}}

/** Pretty string for card display. */
if (!function_exists('keg_pretty_date_from_raw')){
function keg_pretty_date_from_raw($raw){
    $ts = keg_parse_to_ts($raw);
    return $ts ? date_i18n('D, M j Y',$ts) : '';

/** Build a flexible meta_query clause for location searches. */
if (!function_exists('keg_build_location_clause')){
function keg_build_location_clause($raw){
    $raw = trim((string)$raw);
    if($raw===''){ return array(); }

    $needles = array();
    $add_needle = function($val) use (&$needles){
        $val = sanitize_text_field($val);
        if($val!==''){ $needles[$val] = true; }
    };

    $add_needle($raw);

    $parts = preg_split('/[,|]/', $raw);
    if($parts && count($parts)>1){
        foreach($parts as $part){
            $part = trim($part);
            if($part!==''){ $add_needle($part); }
        }
    }

    // fall back to trimmed words when comma separated parts are not provided
    if(count($needles) === 1){
        $words = preg_split('/\s+/', $raw);
        if($words && count($words)>1){
            foreach($words as $word){
                $word = trim($word);
                if($word!==''){ $add_needle($word); }
            }
        }
    }

    if(empty($needles)){ return array(); }

    $clause = array('relation'=>'OR');
    foreach(array_keys($needles) as $needle){
        $clause[] = array(
            'key'     => 'event_city',
            'value'   => $needle,
            'compare' => 'LIKE',
        );
    }

    return $clause;

}}
