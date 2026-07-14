import axios from "axios";

export const CreateNewFolder = async (name,parentId)=>{
    let data;
    await axios.post(
    // eslint-disable-next-line
    react_data.ajax_url,
    new URLSearchParams({
        action: "create_new_folder",
        folder_name: name,
        parent_id:parentId,
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
};