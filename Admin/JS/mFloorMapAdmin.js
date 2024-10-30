

jQuery(document).ready(function(){
    jQuery('del.ReorderArrowUp').closest('table').on('click', function(e){
        if (e.target.tagName !== 'DEL') return;
        var Element= jQuery(e.target);     
        var Form= Element.closest('form');
        // uncheck all checkboxes and check only current
        Form.find('input[name="items[]"]').prop('checked',false);
        Element.closest('tr').find('input[type=checkbox]:eq(0)').prop('checked',true);   
        // inject 'Reorder' into action
        var Opt= new Option('', 'Reorder');
        jQuery("#bulk-action-selector-top").append(Opt).val('Reorder');
        // inject direction into action2
        var Direction= Element.hasClass('ReorderArrowUp') ? 'Up' : 'Dn';
        var Opt= new Option('', Direction);
        jQuery("#bulk-action-selector-bottom").append(Opt).val(Direction);
        // preserve table column 
        Form.append('<input type="hidden" name="orderby" value="Ordering"><input type="hidden" name="order" value="asc">');
        // submit form
        Form.trigger('submit');
    });
});
