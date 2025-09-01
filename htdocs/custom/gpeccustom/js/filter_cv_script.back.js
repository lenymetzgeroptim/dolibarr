
// function fetchJobSkills() {
//     $.ajax({
//         url: '/custom/gpeccustom/ajax/getcvtec_data.php', 
//         type: 'GET',
//         dataType: 'json',
//         success: function(data) {
//             window.jobskills = data; 
//             console.log(data);
//             populateJobFilter(); // Repeupler les filtres après avoir reçu les données
//         },
//         error: function(xhr, status, error) {
//             console.error('An error occurred while fetching jobskills: ' + error);
//             console.log('Réponse brute :', xhr.responseText);
//         }
//     });
// }

// Get unique elements from an array
function getUniqueItems(array, key, label) {
    const unique = new Map();  
    array.forEach(item => {
        if (!unique.has(item[key])) {
            unique.set(item[key], { [key]: item[key], [label]: item[label] });
        }
    });
    return Array.from(unique.values());
}

// Restore previous selection with Select2
function restoreSelection(selectElement, selectedValues) {
    $(selectElement).val(selectedValues).trigger('change.select2', { manual: true });
}

// Initialize Select2 for job and skill filters
function initializeSelect2() {
    $("#jobFilter, #skillFilter, #levelFilter").select2({
        width: '40%',
        placeholder: function() { return $(this).attr('placeholder'); },
        allowClear: true
    });
}

// Populate the job dropdown list
function populateJobFilter() {
    const jobFilter = $('#jobFilter');
    const selectedJobs = jobFilter.val() || [];

    // DocumentFragment to optimize DOM performance
    const fragment = document.createDocumentFragment();
    const emptyOption = document.createElement('option');
    emptyOption.value = ''; // Represents no selection
    emptyOption.textContent = '';  // Empty text to avoid default placeholder showing
    fragment.appendChild(emptyOption);

    // Get unique jobs from jobskills
    const uniqueJobs = getUniqueItems(window.jobskills, 'fk_job', 'job_label');

    // Add options to the fragment
    uniqueJobs.forEach(job => {
        let option = document.createElement('option');
        option.value = job.fk_job;
        option.textContent = job.job_label;
        fragment.appendChild(option);
    });

    // Reset job list and add options in one operation
    jobFilter.empty().append(fragment);

    // Restore previous selection
    restoreSelection('#jobFilter', selectedJobs);

    // Filter skills according to selected jobs
    filterSkills();
}

// Filter skills based on selected jobs
function filterSkills() {
    const selectedJobs = $('#jobFilter').val() || [];
    const skillFilter = $('#skillFilter');
    const selectedSkills = skillFilter.val() || [];

    // Use DocumentFragment for skills
    const fragment = document.createDocumentFragment();
    const emptyOption = document.createElement('option');
    emptyOption.value = ''; // Represents no selection
    emptyOption.textContent = '';  // Empty text to avoid default placeholder showing
    fragment.appendChild(emptyOption);

    // Filter skills based on selected jobs
    const filteredSkills = selectedJobs.length === 0 ? window.jobskills : window.jobskills.filter(item => selectedJobs.includes(item.fk_job));

    // Get unique skills
    const uniqueSkills = getUniqueItems(filteredSkills, 'skillid', 'skill_label');

    // Add options to fragment
    uniqueSkills.forEach(skill => {
        let option = document.createElement('option');
        option.value = skill.skillid;
        option.textContent = skill.skill_label;
        fragment.appendChild(option);
    });

    // Reset skill list and add options in one operation
    skillFilter.empty().append(fragment);

    // Restore previous selection
    restoreSelection('#skillFilter', selectedSkills);

    // Filter levels according to selected skills
    filterLevels();
}

// Filter levels based on selected skills
function filterLevels() {
    const selectedSkills = $('#skillFilter').val() || [];
    const levelFilter = $('#levelFilter');
    const selectedLevels = levelFilter.val() || [];

    // Use DocumentFragment for levels
    const fragment = document.createDocumentFragment();
    const emptyOption = document.createElement('option');
    emptyOption.value = ''; // Represents no selection
    emptyOption.textContent = '';  // Empty text to avoid default placeholder showing
    fragment.appendChild(emptyOption);

    // Filter levels based on selected skills
    const filteredLevels = selectedSkills.length === 0 ? window.jobskills : window.jobskills.filter(item => selectedSkills.includes(item.skillid));

    // Get unique levels
    const uniqueLevels = getUniqueItems(filteredLevels, 'rankorder', 'skill_level');

    // Add options to fragment
    uniqueLevels.forEach(level => {
        let option = document.createElement('option');
        option.value = level.rankorder;
        option.textContent = level.skill_level;
        fragment.appendChild(option);
    });

    // Reset level list and add options in one operation
    levelFilter.empty().append(fragment);

    // Restore previous selection
    restoreSelection('#levelFilter', selectedLevels);
}

// Filter jobs based on selected skills
function filterJobs() {
    const selectedSkills = $('#skillFilter').val() || [];
    const jobFilter = $('#jobFilter');
    const selectedJobs = jobFilter.val() || [];

    // Use DocumentFragment for jobs
    const fragment = document.createDocumentFragment();
    const emptyOption = document.createElement('option');
    emptyOption.value = ''; // Represents no selection
    emptyOption.textContent = '';  // Empty text to avoid default placeholder showing
    fragment.appendChild(emptyOption);

    // Filter jobs based on selected skills
    const filteredJobs = selectedSkills.length === 0 ? window.jobskills : window.jobskills.filter(item => selectedSkills.includes(item.skillid));

    // Get unique jobs
    const uniqueJobs = getUniqueItems(filteredJobs, 'fk_job', 'job_label');

    // Add options to fragment
    uniqueJobs.forEach(job => {
        let option = document.createElement('option');
        option.value = job.fk_job;
        option.textContent = job.job_label;
        fragment.appendChild(option);
    });

    // Reset job list and add options in one operation
    jobFilter.empty().append(fragment);

    // Restore previous selection
    restoreSelection('#jobFilter', selectedJobs);
}

// Send filters to server and update CV results
function updateResults() {
    const selectedJobs = $('#jobFilter').val() || [];
    const selectedSkills = $('#skillFilter').val() || [];
    const selectedLevels = $('#levelFilter').val() || [];

    console.log('Selected Jobs:', selectedJobs);
    console.log('Selected Skills:', selectedSkills);
    console.log('Selected Levels:', selectedLevels);

    $.ajax({
        url: '/custom/gpeccustom/cvtec_list.php?mainmenu=home',
        type: 'POST',
        data: {
            jobs: selectedJobs,
            skills: selectedSkills,
            levels: selectedLevels
        },
        success: function(data) {
            console.log('Response received:', data);

            // Insert desired part of the response
            let tempDiv = $('<div>').html(data);
            let filteredContent = tempDiv.find('#searchFormList').html();
            $('#list_cv').html(filteredContent);

            // Reapply filters after update
            populateJobFilter();
            filterSkills();
            filterJobs();
            filterLevels();
        },
        error: function(xhr, status, error) {
            console.error('An error occurred: ' + error);
        }
    });
}

// Initialize filters on page load
function initializePage() {
    initializeSelect2();
    fetchJobSkills();  // Fetch job skills data via AJAX 

    // Add event listeners for selections and removals in Select2
    $('#jobFilter, #skillFilter, #levelFilter').on('change', function(e, data) {
        if (data && data.manual) return;  // Skip if change is manual
        console.log('Filter Changed:', this.id);
        switch (this.id) {
            case 'jobFilter':
                filterSkills();  // Update skills based on selected jobs
                break;
            case 'skillFilter':
                filterJobs();  // Update jobs based on selected skills
                break;
            case 'levelFilter':
                filterLevels();  // Update levels based on selected skills
                break;
        }
        updateResults();  // Update CV results
    });
}

// Trigger page initialization on document ready
$(document).ready(initializePage);