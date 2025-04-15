function showForm(formID){
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"))
    /* Select all HTML elements with the class "form-box" forEach() to iterate, for each element remove "active" class */
    document.getElementById(formID).classList.add("active") 
    /*emove all active class, then add active to the one passed only */
}



function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}

function handleCategoryChange() {
    const category = document.getElementById('category_id').value;
    const tempOptions = document.getElementById('temp-options');
    const sugarOptions = document.getElementById('sugar-options');

    // Hide both options initially
    tempOptions.style.display = 'none';
    sugarOptions.style.display = 'none';

    if (category === 'temp') {
        tempOptions.style.display = 'block';
    } else if (category === 'sugar') {
        sugarOptions.style.display = 'block';
    }
}

function confirmDeleteCategory(id) {
    if (confirm("Are you sure you want to delete this category?")) {
        window.location.href = 'add_category.php?delete=' + id;
    }
}

function confirmAndShowForm() {
    // if (confirm("Do you want to add a new product?")) {
        document.getElementById('add-product-form').style.display = 'block';
        document.getElementById('add-product-form').scrollIntoView({ behavior: 'smooth' });
    // }
}

function loadProduct(product) {
    const form = document.getElementById('product-info-form');
    form.style.display = 'block';

    document.getElementById('edit_product_id').value = product.id;
    document.getElementById('edit_product_name').value = product.name;
    document.getElementById('edit_product_price').value = product.price;
    document.getElementById('edit_category_id').value = product.category;
    document.getElementById('edit_product_desc').value = product.description || '';

    handleEditCategoryChange(); // update checkbox visibility

    // Pre-check the correct options
    const options = (product.options || '').split(',');
    document.querySelectorAll('#edit-temp-options input[type=checkbox]').forEach(cb => {
        cb.checked = options.includes(cb.value);
    });
    document.querySelectorAll('#edit-sugar-options input[type=checkbox]').forEach(cb => {
        cb.checked = options.includes(cb.value);
    });
}

function handleEditCategoryChange() {
    const selected = document.getElementById('edit_category_id').value;

    // Hide all groups first
    document.querySelectorAll('.checkbox-wrapper').forEach(div => div.style.display = 'none');

    // Show selected group
    if (selected) {
        const target = document.getElementById('checkbox_' + selected);
        if (target) target.style.display = 'block';
    }
}


