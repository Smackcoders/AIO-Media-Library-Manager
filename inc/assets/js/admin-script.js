// wp-react-sidebar.js
document.addEventListener("DOMContentLoaded", function () {
    console.log("Injecting React sidebar...");

    // Prevent duplicates
    if (document.getElementById("my-react-container")) return;

    const sidebar = document.createElement("section");
    sidebar.id = "my-react-container";
    sidebar.innerHTML = `<div id="my-react-root"></div>`;

    // Append BELOW adminbar but ABOVE content
    const wpcontent = document.getElementById("wpcontent");
    if (wpcontent) {
        wpcontent.appendChild(sidebar);
        console.log("React sidebar inserted");
    } else {
        console.error("#wpcontent not found");
    }
});
