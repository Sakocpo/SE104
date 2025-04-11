function showForm(formID){
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"))
    /* Select all HTML elements with the class "form-box"
        forEach() to iterate, for each element remove "active" class */
    document.getElementById(formID).classList.add("active") 
    /* Add active to the class of the form passed */
    /* In short, remove all active class, then add active to the one passed only */
}


// Toggle sidebar visibility
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    sidebar.classList.toggle('active');
}