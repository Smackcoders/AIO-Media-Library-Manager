import axios from "axios";

const postPasteAction = async (action, idsKey, ids, parentId) => {
    let data;

    await axios.post(
        // eslint-disable-next-line
        react_data.ajax_url,
        new URLSearchParams({
            action,
            [idsKey]: JSON.stringify(ids),
            parent_id: parentId,
            // eslint-disable-next-line
            security: react_data.nonce
        })
    )
    .then(res => {
        data = res.data;
    })
    .catch(err => {
        console.error("Paste folder error:", err);
    });

    return data;
};

export const WP_CopyPasteFolders = async (ids, parentId) => (
    postPasteAction("aioml_copy_paste_folders", "copy_ids", ids, parentId)
);

export const WP_CutPasteFolders = async (ids, parentId) => (
    postPasteAction("aioml_cut_paste_folders", "cut_ids", ids, parentId)
);
