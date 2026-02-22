<?php
$qname = $queue->getName() ?:  __('Tickets Export');

// Session cache
$cache = $_SESSION['Export:Q'.$queue->getId()];
if (isset($cache['filename']))
    $filename = $cache['filename'];
else
    $filename = trim(sprintf('%s Tickets - %s.csv',
            Format::htmlchars($queue->getName() ?: ''),
            date('Ymd')));

if (isset($cache['delimiter']))
    $delimiter = $cache['delimiter'];
else
    $delimiter = Internationalization::getCSVDelimiter();

$fields = $queue->getExportFields(false) ?: array();
if (isset($cache['fields']) && $fields)
    $fields = array_intersect_key($fields, array_flip($cache['fields']));

$action = isset($info['action'])
      ? $info['action']
      : '#tickets/export/'.$queue->getId();

// Selected ticket IDs (from GET when opening dialog)
$selected_tids = array();
if (isset($_GET['tids']) && is_array($_GET['tids']))
    $selected_tids = array_filter(array_map('intval', $_GET['tids']));
?>
<div id="tickets-export">
<h3 class="drag-handle"><?php echo Format::htmlchars($qname); ?></h3>
<a class="close" href=""><i class="icon-remove-circle"></i></a>
<hr/>
<?php
if (isset($errors['err'])) { ?>
<div id="msg_error" class="error-banner"><?php echo
Format::htmlchars($errors['err']); ?></div>
<?php
} ?>
<form action="<?php echo $action; ?>" method="post"
name="queue-export" id="queue-export">
  <?php if (!empty($selected_tids)) { ?>
  <div style="padding:6px 10px; background:#e8f4e8; border:1px solid #5cb85c; margin-bottom:8px; border-radius:3px; font-size:12px;">
    <i class="icon-filter" style="color:#3c763d;"></i>&nbsp;
    <strong style="color:#3c763d;"><?php echo sprintf(__('Exportando %d ticket(s) seleccionado(s)'), count($selected_tids)); ?></strong>
    <?php foreach ($selected_tids as $tid) { ?>
    <input type="hidden" name="tids[]" value="<?php echo (int)$tid; ?>">
    <?php } ?>
  </div>
  <?php } else { ?>
  <div style="padding:6px 10px; background:#f5f5f5; border:1px solid #ddd; margin-bottom:8px; border-radius:3px; font-size:12px; color:#777;">
    <i class="icon-list"></i>&nbsp;
    <?php echo __('Exportando todos los tickets de esta cola'); ?>
  </div>
  <?php } ?>
  <div style="overflow-y: auto; height:400px; margin-bottom:5px;">
  <table class="table">
      <tbody>
        <tr class="header">
          <td><small><i class="icon-caret-down"></i>&nbsp;<?php echo
          sprintf('%s <strong class="faded">( <span id="fields-count">%d</span> %s )</strong>',
            __('Check columns to export'),
            count($fields),
            __('selected'));
          ?> </small></td>
        </tr>
      </tbody>
      <tbody class="sortable-rows" id="fields">
        <?php
        foreach (array_merge($fields, CustomQueue::getExportableFields()) as $path  => $label) {
         echo sprintf('<tr style="display: table-row;">
                <td><i class="faded-more
                icon-sort"></i>&nbsp;&nbsp;<label><input
                type="checkbox" name="fields[]" value="%s" %s>
                &nbsp;&nbsp;<span>%s</span></label><td></tr>',
                $path,
                isset($fields[$path]) ? 'checked="checked"' : '',
                @$fields[$path] ?: $label);
        } ?>
      </tbody>
  </table>
  </div>
  <?php
  if ($queue->isSaved()) { ?>
  <div id="save-changes" class="hidden" style="padding-top:5px; border-top: 1px dotted #ddd;">
    <span><i class="icon-bell-alt" style="color:red;"></i>&nbsp;
     <label><input type="checkbox" name='save-changes' >&nbsp;Save export preference changes</label> </span>
  </div>
  <?php
  } ?>
  <div style="margin-top:10px;"><small><a href="#"
    id="more"><i class="icon-caret-right"></i>&nbsp;<?php echo __('Advanced CSV Options'); ?></a></small></div>
  <div id="more-options" class="hidden" style="padding:5px; border-top: 1px dotted #777;">
      <div><span class="faded" style="width:60px;"><?php echo __('Filename'); ?>: </span><input
        name="filename" type="text" size="40"
        value="<?php echo Format::htmlchars($filename); ?>"></div>
      <div><span class="faded" style="width:60px;"><?php echo __('Delimiter'); ?>: </span><input
        name="csv-delimiter" type="text" maxlength="1"  size=10
        value="<?php echo $delimiter; ?>"
        placeholder=", (Comma)" maxlength="1" /></div>
  </div>
  <p class="full-width">
    <span class="buttons pull-left">
        <input type="reset"  id="reset"  value="<?php echo __('Reset'); ?>">
        <input type="button" name="cancel" class="close"
        value="<?php echo __('Cancel'); ?>">
    </span>
    <span class="buttons pull-right">
        <input type="submit" id="export-submit-btn" value="<?php echo __('Export'); ?>">
    </span>
   </p>
   <div id="export-loading" style="display:none; padding:8px 10px; background:#fff3cd; border:1px solid #ffc107; border-radius:3px; margin-top:5px; text-align:center;">
     <i class="icon-spinner icon-spin"></i>&nbsp;
     <strong><?php echo __('Generando exportacion, por favor espere...'); ?></strong>&nbsp;
     <span class="faded"><?php echo __('No cierre esta ventana.'); ?></span>
   </div>
</form>
</div>
<div class="clear"></div>
<script>
+function() {
   // Return a helper with preserved width of cells
   var fixHelper = function(e, ui) {
      ui.children().each(function() {
          $(this).width($(this).width());
      });
      return ui;
   };
   // Sortable tables for dynamic forms objects
   $('.sortable-rows').sortable({
       'helper': fixHelper,
       'cursor': 'move',
       'stop': function(e, ui) {
            $('div#save-changes').fadeIn();
       }
   });

   $('#more').click(function() {
        var more = $(this);
        $('#more-options').slideToggle('fast', function(){
           if ($(this).is(":hidden"))
            more.find('i').removeClass('icon-caret-down').addClass('icon-caret-right');
           else
             more.find('i').removeClass('icon-caret-right').addClass('icon-caret-down');
        });
        return false;
    });

   $(document).on('change', 'tbody#fields input:checkbox', function (e) {
       var f = $(this).closest('form');
       var count = $("input[name='fields[]']:checked", f).length;
       $('div#save-changes', f).fadeIn();
       $('span#fields-count', f).html(count);
     });

   $('#queue-export').on('submit', function() {
       var $btn = $('#export-submit-btn');
       if ($btn.prop('disabled')) return false; // evitar doble clic
       $btn.prop('disabled', true).val('<?php echo __('Exportando...'); ?>');
       $('#export-loading').show();
   });

   $(document).on('click', 'input#reset', function(e) {
        var f = $(this).closest('form');
        $('input.save-changes', f).prop('checked', false);
        $('span#fields-count', f).html(<?php echo count($fields); ?>);
        $('div#save-changes', f).hide();
    });
}();
</script>
