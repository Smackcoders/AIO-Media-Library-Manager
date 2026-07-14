import axios from "axios"

export const WP_DeleteFolder = async (id) => {
    let data;
    await axios.post(
    // eslint-disable-next-line
    react_data.ajax_url,
    new URLSearchParams({
        action: "delete_aioml_folder",
        term_id: id,
        // eslint-disable-next-line
        security:react_data.nonce
    })
    )
    .then(res => {
    console.log("Response:", res.data);
    data = res.data;
    })
    .catch(err => {
    console.error("Error:", err);
    });

    return data;
}