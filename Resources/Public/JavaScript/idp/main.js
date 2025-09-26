function setMetadata() {
    jQuery('#idp_metadata_save').attr('value', 'mosaml_metadata');
    jQuery('#sp_settings').submit();
}

function idp_metadata_save() {
    jQuery('#metadata_download').attr('value', 'save_connector_settings');
    jQuery('#saml_form').submit();
}

function set_value() {
    jQuery('#metadata_download').attr('value', 'mosaml_metadata_download');
    jQuery('#saml_form').submit();
}

function save_sp_data() {
    jQuery('#metadata_download').attr('value', 'save_sp_settings');
    jQuery('#sp_settings').submit();
}

function set_acs() {
    var responseElement = document.getElementById("response");
    var acsElement = document.getElementById("acs_url");
    if (responseElement && acsElement) {
        acsElement.value = responseElement.value;
    }
}

function set_entityid() {
    var siteBaseUrlElement = document.getElementById("site_base_url");
    var spEntityIdElement = document.getElementById("sp_entity_id");
    if (siteBaseUrlElement && spEntityIdElement) {
        spEntityIdElement.value = siteBaseUrlElement.value;
    }
}

function copyURL() {
    const copyText = document.getElementById("metadata_url");
    if (copyText) {
        navigator.clipboard.writeText(copyText.href || copyText.textContent);

        var tooltip = document.getElementById("myTooltip");
        if (tooltip) {
            tooltip.innerHTML = "URL Copied!";
        }
    }
}

function outFunc() {
    var tooltip = document.getElementById("myTooltip");
    if (tooltip) {
        tooltip.innerHTML = "Copy to clipboard";
    }
}

function add_provider() {
    console.log("Adding new provider...");
}

$(document).ready(function () {
    // Initialize tab based on session
    let session = document.getElementById('taby').value;
    if (session == "Identity_Provider") {
        openTab('Identity_Provider');
    } else if (session == "Providers") {
        openTab('Providers');
    } else if (session == "Support") {
        openTab('Support');
    } else if (session == "Upgrade") {
        openTab('Upgrade');
    } else {
        openTab('Providers'); // Changed default from Account to Providers
    }
    
    document.getElementById('providers_tab_btn').addEventListener('click', function() {
        removeFlashMessage();
        openTab('Providers');
    });
    
    document.getElementById('saml_tab_btn').addEventListener('click', function() {
        removeFlashMessage();
        openTab('Identity_Provider');
    });
    
    document.getElementById('upgrade_tab_btn').addEventListener('click', function() {
        removeFlashMessage();
        openTab('Upgrade');
    });
    
    // Initialize other event listeners
    initializeEventListeners();
});

function openTab(activeTab) {
    let leftContainer = document.getElementById("leftContainer");
    if (leftContainer) {
        leftContainer.classList.add("showElement");
        leftContainer.classList.remove("hideElement");
    }

    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    if (activeTab !== "Upgrade") {
        let activeTabElement = document.getElementById(activeTab);
        if (activeTabElement) {
            activeTabElement.style.display = "block";
        }
        
        let supportElement = document.getElementById("Support");
        if (supportElement) {
            supportElement.style.display = "block";
        }
        
        // Update active tab button
        let tabButtonId = activeTab.toLowerCase() + '_tab_btn';
        if (activeTab === 'Identity_Provider') {
            tabButtonId = 'saml_tab_btn';
        }
        let activeTabButton = document.getElementById(tabButtonId);
        if (activeTabButton) {
            activeTabButton.classList.add("active");
        }
    } else {
        let upgradeElement = document.getElementById(activeTab);
        if (upgradeElement) {
            upgradeElement.style.display = "block";
        }
        
        if (leftContainer) {
            leftContainer.classList.replace("showElement", "hideElement");
        }
        
        let upgradeTabButton = document.getElementById('upgrade_tab_btn');
        if (upgradeTabButton) {
            upgradeTabButton.classList.add("active");
        }
    }
}

function removeFlashMessage() {
    document.querySelectorAll('.typo3-messages').forEach(function (a) {
        a.remove();
    });
}

// Utility function to safely get element by ID
function getElementById(id) {
    return document.getElementById(id);
}

// Utility function to safely add event listeners
function addEventListenerSafe(elementId, event, handler) {
    let element = getElementById(elementId);
    if (element) {
        element.addEventListener(event, handler);
    }
}

// Initialize all event listeners when DOM is ready
function initializeEventListeners() {
    addEventListenerSafe('providers_form', 'submit', function(e) {
        console.log('Providers form submitted');
    });
    
    addEventListenerSafe('saml_form', 'submit', function(e) {
        console.log('SAML form submitted');
    });
    
    addEventListenerSafe('sp_settings', 'submit', function(e) {
        console.log('SP settings form submitted');
    });
    
    addEventListenerSafe('support_form', 'submit', function(e) {
        console.log('Support form submitted');
    });
    
    // Add event listeners for button clicks
    addEventListenerSafe('add_provider', 'click', function(e) {
        console.log('Add provider button clicked');
        // Form will submit automatically since it's type="submit"
    });
    
    addEventListenerSafe('submit_saml_form', 'click', function(e) {
        console.log('SAML form submit button clicked');
        e.preventDefault(); // Prevent default form submission
        idp_metadata_save();
    });
    
    addEventListenerSafe('sp_setting_form', 'click', function(e) {
        console.log('SP settings form submit button clicked');
        e.preventDefault(); // Prevent default form submission
        save_sp_data();
    });
    
    addEventListenerSafe('copy', 'click', function(e) {
        console.log('Copy URL button clicked');
        e.preventDefault();
        copyURL();
    });
    
    addEventListenerSafe('copy', 'mouseout', function(e) {
        outFunc();
    });
    
    addEventListenerSafe('download_metadata', 'click', function(e) {
        e.preventDefault();
        set_value();
    });
    
    addEventListenerSafe('idp-delete-form', 'submit', function(e) {
    });
    
    addEventListenerSafe('idp-edit-form', 'submit', function(e) {
    });
}
