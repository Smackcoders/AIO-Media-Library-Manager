import axios from "axios";

export const fetchAllFolders = async (setItems) => {
    const formData = new FormData();
    formData.append("action", "fetch_all_folder");
    // eslint-disable-next-line
    formData.append("security", react_data.nonce);

    try {
        // eslint-disable-next-line
        const response = await axios.post(react_data.ajax_url, formData);
        const res = response.data;

        let raw = [];

        // Handle different response structures
        if (Array.isArray(res)) {
            raw = res;
        } else if (res && res.success && Array.isArray(res.data)) {
            // standard wp_send_json_success($items)
            raw = res.data;
        } else if (res && res.success && res.data && Array.isArray(res.data.data)) {
            // wp_send_json_success(['data' => $items])
            raw = res.data.data;
        } else if (res && res.data && Array.isArray(res.data)) {
            // generic
            raw = res.data;
        }

        console.log("Fetched Folders Response:", res);
        console.log("Extracted Raw Folders:", raw);

        if (!Array.isArray(raw)) {
            console.error("Expected array of folders, got:", raw);
            setItems([]); // Set empty to clear loading state if any
            return;
        }

        const folders = raw.map(f => ({
            term_id: Number(f.term_id),
            name: f.name,
            parent: Number(f.parent || 0),
            id: Number(f.id || f.term_id)
        }));

        setItems(folders);

    } catch (e) {
        console.error("Fetch error:", e);
    }
};
