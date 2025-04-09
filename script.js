function showForm(formID){
    document.querySelectorAll(".form-box").forEach(form => form.classList.remove("active"))
    /* Select all HTML elements with the class "form-box, the remove all active */
    document.getElementById(formID).classList.add("active") 
    /* remove all active class, then add active to the one passed only */
}