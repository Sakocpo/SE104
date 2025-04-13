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