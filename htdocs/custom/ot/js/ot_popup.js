
/**
 * Fonction pour afficher la popup de confirmation de suppression de contact
 */
function confirmDeleteContactWithOT(projectId, projectRef, contactId, lineId, baseUrl) {
    var message = "Voulez-vous créer un OT lors de la suppression de ce contact du projet " + projectRef + " ?";
    
    if (confirm(message)) {
        // L'utilisateur veut créer un OT
        window.location.href = baseUrl + "?id=" + projectId + 
                              "&action=deletecontact&contactid=" + contactId + 
                              "&lineid=" + lineId + "&confirm_delete_ot=yes&token=" + 
                              getToken();
    } else {
        // L'utilisateur ne veut pas créer d'OT
        window.location.href = baseUrl + "?id=" + projectId + 
                              "&action=deletecontact&contactid=" + contactId + 
                              "&lineid=" + lineId + "&confirm_delete_ot=no&token=" + 
                              getToken();
    }
}

/**
 * Récupérer le token CSRF
 */
function getToken() {
    var tokenElement = document.querySelector('input[name="token"]');
    return tokenElement ? tokenElement.value : '';
}
