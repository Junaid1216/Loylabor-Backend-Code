<script>
    var menus = {
        "deleteFailed" : '{{__("Delete Failed")}}',
        "updateFailed" : '{{__("Update Failed")}}',
        "addItem" : '{{__("Created successfully")}}',
        "itemAddFailed" : '{{__("Item failed to add")}}',
        "updateItem" : '{{__("Updated successfully")}}',
        "deleteItem" : '{{__("Deleted successfully")}}',
        "deleteItemAlert" : '{{__("Do you want to delete this item ?")}}',
        "updated" : '{{__("Updated Successfully")}}',
        "failed" : '{{__("Operation Failed")}}',
    };
    var menuNameUpdate = '{{ route("admin.menu-name.update") }}';
    var menuUpdate = '{{ route("admin.custom-menu.update") }}';
    var addItemUrl = '{{ route("admin.custom-menu.items.create") }}';
    
    var csrfToken = "{{ csrf_token() }}";
</script>