<?php

//Post for Ajax

?>
<script>
//Fonction to send the filters to the server and update the list of results
function updateResults() {
     selectedJobs = Array.from(document.getElementById('jobFilter').selectedOptions).map(option => option.value);
    const selectedSkills = Array.from(document.getElementById('skillFilter').selectedOptions).map(option => option.value);
console.log(selectedJobs);
    $.ajax({
        url: '/custom/gpeccustom/cvtec_list.php?mainmenu=home',  // URL du script PHP qui va traiter les filtres
        type: 'POST',
        data: {
            jobs: selectedJobs,
            skills: selectedSkills
        },
        success: function(response) {
            // dispaly result
            $('#mainbody').html(response);
        },
        error: function(xhr, status, error) {
            console.error('An error occurred: ' + error);
        }
    });
}


document.getElementById('jobFilter').addEventListener('change', updateResults);
document.getElementById('skillFilter').addEventListener('change', updateResults);

</script>
<?