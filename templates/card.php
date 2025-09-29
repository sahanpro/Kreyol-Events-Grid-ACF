<?php
$ename = function_exists('get_field') ? get_field('event_name') : '';
$ecity = function_exists('get_field') ? get_field('event_city') : '';
$ecat  = function_exists('get_field') ? get_field('event_category') : '';
$edate = function_exists('get_field') ? get_field('event_date') : '';
$emode = function_exists('get_field') ? get_field('event_mode') : '';
$venue = function_exists('get_field') ? get_field('event_venue') : '';
$addr  = function_exists('get_field') ? get_field('event_address') : '';

$display_date = '';
if ($edate) {
    if (is_numeric($edate))      $display_date = date_i18n('D, M j Y', intval($edate));
    elseif (strtotime($edate))   $display_date = date_i18n('D, M j Y', strtotime($edate));
} else {
    $tec = get_post_meta(get_the_ID(), '_EventStartDate', true);
    if ($tec) $display_date = date_i18n('D, M j Y', strtotime($tec));
}
$title = $ename ? $ename : get_the_title();
?>
<article class="keg-card">
  <a class="keg-thumb" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr($title); ?>">
      <?php if(has_post_thumbnail()) the_post_thumbnail('large'); ?>
  </a>
  <div class="keg-body">
    <h3 class="keg-title"><a href="<?php the_permalink(); ?>"><?php echo esc_html($title); ?></a></h3>
    <?php if($display_date): ?><p class="keg-datetime"><?php echo esc_html($display_date); ?></p><?php endif; ?>
    <?php if($venue): ?><p class="keg-venue"><?php echo esc_html($venue); ?></p><?php endif; ?>
    <?php if($addr): ?><p class="keg-address"><?php echo esc_html($addr); ?></p><?php endif; ?>
    <div class="keg-meta">
      <?php if($ecity): ?><span class="keg-chip"><?php echo esc_html($ecity); ?></span><?php endif; ?>
      <?php if($ecat): ?><span class="keg-chip"><?php echo esc_html($ecat); ?></span><?php endif; ?>
      <?php if($emode): ?><span class="keg-chip"><?php echo esc_html($emode); ?></span><?php endif; ?>
    </div>
  </div>
</article>
