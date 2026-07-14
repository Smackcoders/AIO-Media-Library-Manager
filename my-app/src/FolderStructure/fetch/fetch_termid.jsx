
import apiFetch from "@wordpress/api-fetch";

// Register custom taxonomy query property for wp.media globally on script load
if (typeof window !== "undefined" && window.wp && window.wp.media && window.wp.media.model && window.wp.media.model.Query && window.wp.media.model.Query.propDefs) {
    window.wp.media.model.Query.propDefs.attachment_category = 'attachment_category';
}

const stripHTML = (html) => {
    const div = document.createElement("div");
    div.innerHTML = html || "";
    return div.textContent || div.innerText || "";
};

export const filterMediaLibraryByFolder = (termId) => {
    console.log("Filtering media library by folder:", termId);

    /* eslint-disable */
    const isListMode = document.querySelector('.wp-list-table.media') !== null;

    if (isListMode) {
        console.log("Detected LIST MODE → reloading page");
        if (!window.confirm("you are in list mode it reloads your page,are you sure you want to reload ? ")) return;
        localStorage.setItem("selectedFolder", termId);
        window.location = `upload.php?mode=list&attachment_category=${termId}`;
        return;
    }

    // GRID mode logic
    const frame = wp.media?.frame;
    if (!frame) return;

    const state = frame.state();
    const library = state.get('library');
    if (!library) return;

    library.props.set({ attachment_category: termId });

    /* eslint-enable */
};
