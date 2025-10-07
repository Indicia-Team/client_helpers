
(function ($) {

  $(document).ready(function () {

    $('a.report-information').on('click', function (evt) {
      var buttons = {};
      if (typeof indiciaData.informationDialog === 'undefined') {
        buttons[indiciaData.informationCloseButton] = function() {
          $(this).dialog('close');
        };
        indiciaData.informationDialog = $('<div>' + indiciaData.information + '</div>').dialog(
          {
            title: indiciaData.informationDialogTitle,
            dialogClass: "no-close",
            buttons: buttons,
            width: $('#controls-table').width()+30
          }
        );
      } else if(!indiciaData.informationDialog.dialog("isOpen")) {
        indiciaData.informationDialog.dialog("option", "width", $('#controls-table').width()+30);
        indiciaData.informationDialog.dialog("open");
      }
    });


    indiciaData.copyClipboard = function(elementId) {
      var body = document.body, range, sel, el;

      el = document.getElementById(elementId);
      range = document.createRange();
      sel = window.getSelection();
      sel.removeAllRanges();
      try {
          range.selectNodeContents(el);
          sel.addRange(range);
      } catch (e) {
          range.selectNode(el);
          sel.addRange(range);
      }
      document.execCommand("Copy");
      sel.removeAllRanges();
    };

    indiciaData.copyImageToClipboard = function(link, elementId) {
      if (navigator.userAgent.indexOf("Firefox") > 0) {
        var buttons = {};

        buttons[indiciaData.informationCloseButton] = function() {
          $(this).dialog('close'); }

        $('<p>Copying the graph to the clipboard is not currently available when using the Firefox browser.</p>').dialog({
            title: 'Not available',
            dialogClass: "no-close",
            buttons: buttons,
          });
      } else {
        $(link).append('<span class="copyImageToClipboardMsg"> - Copying: please wait</span>');
        setTimeout(function() {
          html2canvas(document.getElementById(elementId))
            .then(canvas=>{
              canvas.toBlob(blob => {
                navigator.clipboard.write([new ClipboardItem({[blob.type]: blob})])
              })
              $('.copyImageToClipboardMsg').remove();
            });
        }, 10);
      }
    }

    if (navigator.userAgent.indexOf("Firefox") > 0) {
      $('.copyCanvas').append(' - NOT AVAILABLE IN FIREFOX').attr('disabled', 'disabled');
    }
  });

}(jQuery));