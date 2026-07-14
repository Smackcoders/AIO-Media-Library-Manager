/**
 * WordPress Media Library → React Folder Tree Drag Support
 * + Safe Uploader Patch
 * + Add New → redirect to media-new.php
 * + Hide "Select Files" uploader box
 */

(function ($) {

    /* ============================================================
       1. SAFE PATCH — REMOVE BLUE OVERLAY WITHOUT BREAKING UPLOADER
       ============================================================ */
    function patchUploaderWindow() {
        try {
            const UploaderWindow = wp?.media?.view?.UploaderWindow;

            if (!UploaderWindow?.prototype) {
                throw new Error("UploaderWindow not ready");
            }

            const proto = UploaderWindow.prototype;

            // ONLY disable overlay (do NOT override ready/prepare/bindHandlers)
            proto.show = function () { return this; };
            proto.hide = function () { return this; };

            console.log("✔ WP UploaderWindow safely patched");
        } catch (e) {
            setTimeout(patchUploaderWindow, 20);
        }
    }

    patchUploaderWindow();

    /* ============================================================
       2. HIDE "SELECT FILES" SECTION (media modal)
       ============================================================ */
    function hideSelectFiles() {
        // Hide inline uploader inside modal
        $(".uploader-inline").hide();
    }

    // React re-renders — listen for new modal content
    $(document).on("DOMNodeInserted", function () {
        hideSelectFiles();
    });

    /* ============================================================
       3. GRID VIEW — GET SELECTED MEDIA ITEMS
       ============================================================ */
    function getSelectedGridMediaIds() {
        return $(".attachment.selected")
            .map(function () {
                return $(this).data("id");
            })
            .get();
    }

    /* ============================================================
       4. GRID VIEW — ENABLE DRAG
       ============================================================ */
    function enableGridDrag() {

        $(document).on("mouseenter", ".attachment", function () {
            const id = $(this).data("id");
            if (!id) return;
            if ($(this).attr("draggable")) return;
            $(this).attr("draggable", "true");
        });

        $(document).on("dragstart", ".attachment", function (e) {
            const id = $(this).data("id");
            if (!id) return;

            const selected = getSelectedGridMediaIds();
            const ids = selected.length ? selected : [id];

            if (selected.length) {
                $(".attachment.selected").addClass("aioml-dragging");
            } else {
                $(this).addClass("aioml-dragging");
            }

            e.originalEvent.dataTransfer.setData(
                "application/json",
                JSON.stringify({ attachmentIds: ids })
            );

            e.originalEvent.dataTransfer.effectAllowed = "move";
        });

        $(document).on("dragend", ".attachment", function (e) {
            $(".attachment").removeClass("aioml-dragging");
        });
    }

    /* ============================================================
       5. LIST VIEW — ENABLE DRAG
       ============================================================ */
    function enableListDrag() {

        $("#the-list").on("mouseenter", "tr", function () {
            const id = $(this).attr("id")?.replace("post-", "");
            if (!id) return;
            if ($(this).attr("draggable")) return;
            $(this).attr("draggable", "true");
        });

        $("#the-list").on("dragstart", "tr", function (e) {
            const id = $(this).attr("id")?.replace("post-", "");
            if (!id) return;

            $(this).addClass("aioml-dragging");

            e.originalEvent.dataTransfer.setData(
                "application/json",
                JSON.stringify({ attachmentIds: [id] })
            );

            e.originalEvent.dataTransfer.effectAllowed = "move";
        });

        $("#the-list").on("dragend", "tr", function (e) {
            $(this).removeClass("aioml-dragging");
        });
    }

    /* ============================================================
       6. FOLDER MOVE EVENT
       ============================================================ */
    window.addEventListener("media_folder:move", function (e) {
        const { folderId, attachmentIds } = e.detail;
        console.log("📁 Move to Folder:", folderId);
        console.log("📦 Attachment IDs:", attachmentIds);
        let formData = new FormData();
        formData.append("action","move_attachment_to_term_id");
        formData.append("folder_id",folderId);
        formData.append("attachment_id",attachmentIds[0]);
        formData.append("security",dnd_data.nonce);
        fetch(dnd_data.ajax_url,{
            method:"POST",
            body:formData
        })
        .then(res => res.json())
        .then(data=>{
            console.log("the dnd response: ",data);
        })
        .catch(error=>{
            console.error(error);
        })
    });

    /* ============================================================
       7. REPLACE "ADD NEW" BUTTON → MEDIA-NEW.PHP
       ============================================================ */
    $(document).on("click", ".page-title-action, .upload-view-toggle", function (e) {

        if (window.location.pathname.includes("upload.php")) {
            e.preventDefault();
            window.location.href = "media-new.php";
        }
    });

    /* ============================================================
       8. INIT
       ============================================================ */
    $(document).ready(function () {
        enableGridDrag();
        enableListDrag();
        hideSelectFiles(); // in case modal already open

        console.log("WP Media Drag → React Tree: ACTIVE");
    });

})(jQuery);
