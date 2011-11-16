<?php

/*
 * Feed River Wire
 * @author Jeremy Felt <jeremy.felt@gmail.com>
 * @license MIT License - see license.txt
 */

/*  Inserts Google Analytics at the bottom of your river if you
    have added your Google Analytics ID to includes/config.php */
?>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', '<?php echo $google_analytics_id; ?>']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>