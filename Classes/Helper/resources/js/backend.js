$(document).ready(function () {

    let session = document.getElementById('taby').value;
    if (session == "Service_Provider") {
        openTab('Service_Provider');
    } else if (session == "Identity_Provider") {
        openTab('Identity_Provider');
    } else if (session == "Support") {
        openTab('Support');
    } else if (session == "" || session == "Account" || session === null) {
        openTab('Account');
    }
});

function openTab(activeTab) {

    document.getElementById("leftContainer").classList.add("showElement");
    document.getElementById("leftContainer").classList.remove("hideElement");

    let i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tabcontent");

    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }

    if (activeTab !== "Support") {
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("Support").style.display = "block";
        document.getElementById(activeTab + "_Tab").classList.add("active");
    } else {
        document.getElementById(activeTab).style.display = "block";
        document.getElementById("leftContainer").classList.replace("showElement", "hideElement");
        document.getElementById("Support_Tab").classList.add("active");
    }
}

function removeFlashMessage() {
    document.querySelectorAll('.typo3-messages').forEach(function (a) {
        a.remove();
        console.log("remove typo3 messages.");
    });
}

function addCustomAttribute() {
    // console.log("Appending a Custom Attribute.");

    var val = $("#this_attribute").val();

    if (val.length > 0) {
        if ($("#custom_attrs_form").has($("#" + val)).length) {
            console.log("Element exists with this id");
        } else {
            var div = generateAttributeDiv($("#this_attribute").val())
            $("#submit_custom_attr").before(div);
            $("#this_attribute").val("");
        }
    } else {
        console.log("Enter a valid name.");
    }

}

function generateAttributeDiv(name) {

    var attributeDiv = $("<div>", {'class': 'gm-div', 'id': name + 'Div'});
    var labelForAttr = $("<label>", {'for': name, 'class': 'form-control gm-input-label'}).text(name);
    var inputAttr = $("<input>", {
        'id': name,
        'name': name,
        'class': 'form-control gm-input',
        'type': 'text',
        'placeholder': 'Enter name of IDP attribute'
    });

    attributeDiv.append(labelForAttr);
    attributeDiv.append(inputAttr);

    return attributeDiv;

}
