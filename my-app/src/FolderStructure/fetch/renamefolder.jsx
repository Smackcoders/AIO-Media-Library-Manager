import axios from "axios"

export const WP_Aioml_RenameFolder = async (id,foldername) => {
    let data;
    await axios.post(
    // eslint-disable-next-line
    react_data.ajax_url,
    new URLSearchParams({
        action: "aioml_rename_folder",
        folder_id: id,
        new_name:foldername,
        // eslint-disable-next-line
        security:react_data.nonce
    })
    )
    .then(res => {
        console.log("Rename Response:", res.data);
        data = res.data;
    })
    .catch(err => {
        console.error("RenameError:", err);
    });

    return data;
}