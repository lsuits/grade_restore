
// $Id: selection.js,v 1.0 2008/04/08 10:00:43 pcali1 Exp $

/**
 * Very simple little interface functions for the list and config pages
 */

function include_toggle(checkboxPattern, selected) {
    var checkboxes = document.getElementsByTagName("INPUT");
    for (var i=0; i< checkboxes.length; i++) {
        if (checkboxes[i].name.match(checkboxPattern)){
            checkboxes[i].checked = selected;
        }
    }
}

function toggleInnerRows(rowNames) {
    //Internet Explorer is so stupid; the style object only has two display types apparently, thus the embedded terinary operator :(
    var browser = navigator.appName;
    var x=document.getElementsByName(rowNames);
    for (var i=0; i< x.length; i++) {
        (x[i].style.display == "none") ? x[i].style.display = (browser == "Microsoft Internet Explorer") ? "block" : "table-row" : x[i].style.display = "none";
    }
}

function processingRestore() {
    document.getElementById('sr_flash_message').style.display = "block";
    var animFlash = new YAHOO.util.Anim('sr_flash_message', {
                    height: { to: 28}
                    }, 0.25);
    animFlash.animate();
}
