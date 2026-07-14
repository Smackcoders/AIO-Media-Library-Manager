// import React from "react";
// import { TreeItem } from "@mui/x-tree-view";
// import FolderIcon from "@mui/icons-material/Folder";
// import { Box } from "@mui/material";
// import { LabelStyle, InputStyle } from "./treeStyles";
// import { filterMediaLibraryByFolder } from "./fetch/fetch_termid";
// import { WP_Aioml_RenameFolder } from "./fetch/renamefolder";

// const RenderTree = ({
//   node,
//   level,
//   selectedId,
//   setSelectedId,
//   editingId,
//   setEditingId,
//   onRename,
//   onCopy,
//   onCut,
//   onPaste,
//   onDelete,
//   onContextMenu,
//   onDropFiles,
//   expandedItems,
//   setExpandedItems,
//   multiSelectMode,
//   checkedIds,
//   setCheckedIds,
// }) => {
//   const [newName, setNewName] = React.useState(node.name);
//   const [isOver, setIsOver] = React.useState(false);
//   const expandTimeoutRef = React.useRef(null);

//   React.useEffect(() =>{
//      setNewName(node.name)
//     }, [node.name]);

//   const isEditing = editingId === node.term_id;
//   const hasChildren = node.children && node.children.length > 0;

//   const commitRename = async () => {
//     let data = await WP_Aioml_RenameFolder(node.term_id,newName);
//     console.log(data);
//     onRename(node.term_id, newName);
//     setEditingId(null);
//   };

//   const handleDragOver = (event) => {
//     // prevent default so drop can happen
//     event.preventDefault();
//     setIsOver(true);

//     // Auto-expand after a delay if the node has children
//     if (hasChildren) {
//       // don't set multiple timers
//       if (!expandTimeoutRef.current) {
//         expandTimeoutRef.current = setTimeout(() => {
//           expandTimeoutRef.current = null;
//           const key = String(node.term_id);
//           if (!expandedItems.includes(key)) {
//             setExpandedItems((prev) => {
//               localStorage.setItem("Expanedfolders", JSON.stringify([...prev, key]));
//               return [...prev, key]
//             });
//           }
//         }, 500); // 500ms delay
//       }
//     }
//   };

//   const clearExpandTimeout = () => {
//     if (expandTimeoutRef.current) {
//       clearTimeout(expandTimeoutRef.current);
//       expandTimeoutRef.current = null;
//     }
//   };

//   const handleDragLeave = (event) => {
//     event.preventDefault();
//     setIsOver(false);
//     clearExpandTimeout();
//   };

//   const handleDrop = (event) => {
//     event.preventDefault();
//     setIsOver(false);
//     clearExpandTimeout();

//     // WordPress media drag (JSON payload)
//     const wpData = event.dataTransfer.getData("application/json");
//     if (wpData) {
//       try {
//         const { attachmentIds } = JSON.parse(wpData);
//         if (attachmentIds && onDropFiles) {
//           onDropFiles(node, { attachmentIds }, event);
//         }
//         return;
//       } catch (e) {
//         console.warn("Invalid WP drag payload", e);
//       }
//     }

//     // OS files
//     if (event.dataTransfer.files && event.dataTransfer.files.length > 0) {
//       const files = Array.from(event.dataTransfer.files);
//       console.log("Dropped OS files:", files);
//       if (onDropFiles) {
//         onDropFiles(node, { files }, event);
//       }
//     }
//   };

//   // cleanup on unmount
//   React.useEffect(() => {
//     return () => clearExpandTimeout();
//     // eslint-disable-next-line react-hooks/exhaustive-deps
//   }, []);

//   const isChecked = checkedIds.includes(node.term_id);

//   const handleCheckChange = (e) => {
//     e.stopPropagation();
//     const checked = e.target.checked;
//     setCheckedIds((prev) => {
//       if (checked) {
//         if (prev.includes(node.term_id)) return prev;
//         return [...prev, node.term_id];
//       } else {
//         return prev.filter((id) => id !== node.term_id);
//       }
//     });
//   };

//   return (
//     <TreeItem
//       itemId={String(node.term_id)}
//       sx={{ ml: level * 0.6 }}
//       label={
//         <Box
//           sx={{
//             ...LabelStyle,
//             display: "flex",
//             alignItems: "center",
//             borderRadius: "4px",
//             transition: "0.15s",
//             backgroundColor: isOver ? "#b3acf0ff" : "transparent",
//           }}
//           onClick={(e) => {
//             e.stopPropagation();
//             setSelectedId(node.term_id);
//             console.log("Selected Folder:", node.term_id);
//             localStorage.setItem("selectedFolder", node.term_id);
//             filterMediaLibraryByFolder(node.term_id,setSelectedId);
//           }}
//           onContextMenu={(e) => onContextMenu(e, node.term_id)}
//           onDragOver={handleDragOver}
//           onDragLeave={handleDragLeave}
//           onDrop={handleDrop}
//         >
//           {/* Checkbox for multi-select mode */}
//           {multiSelectMode && (
//             <input
//               type="checkbox"
//               checked={isChecked}
//               onChange={handleCheckChange}
//               onClick={(e) => e.stopPropagation()}
//               style={{ marginRight: 6 }}
//             />
//           )}

//           <FolderIcon className="folder-icon" style={{ fontSize: 18 }} />

//           {isEditing ? (
//             <input
//               autoFocus
//               value={newName}
//               onChange={(e) => setNewName(e.target.value)}
//               onBlur={commitRename}
//               onKeyDown={(e) => {
//                 if (e.key === "Enter") commitRename();
//                 if (e.key === "Escape") setEditingId(null);
//               }}
//               style={InputStyle}
//               onClick={(e) => e.stopPropagation()}
//               onMouseDown={(e) => e.stopPropagation()}
//             />
//           ) : (
//             node.name
//           )}
//         </Box>
//       }
//     >
//       {node.children?.map((child) => (
//         <RenderTree
//           key={child.term_id}
//           node={child}
//           level={level + 1}
//           selectedId={selectedId}
//           setSelectedId={setSelectedId}
//           editingId={editingId}
//           setEditingId={setEditingId}
//           onRename={onRename}
//           onCopy={onCopy}
//           onCut={onCut}
//           onPaste={onPaste}
//           onDelete={onDelete}
//           onContextMenu={onContextMenu}
//           onDropFiles={onDropFiles}
//           expandedItems={expandedItems}
//           setExpandedItems={setExpandedItems}
//           multiSelectMode={multiSelectMode}
//           checkedIds={checkedIds}
//           setCheckedIds={setCheckedIds}
//         />
//       ))}
//     </TreeItem>
//   );
// };

// export default React.memo(RenderTree);
import React from "react";
import { TreeItem } from "@mui/x-tree-view";
import FolderIcon from "@mui/icons-material/Folder";
import { Box, Checkbox } from "@mui/material";
import { LabelStyle, InputStyle, aiomlTokens, systemFolderLabelSx } from "./treeStyles";
import { filterMediaLibraryByFolder } from "./fetch/fetch_termid";
import { WP_Aioml_RenameFolder } from "./fetch/renamefolder";

const RenderTree = ({
  node,
  level,
  selectedId,
  setSelectedId,
  editingId,
  setEditingId,
  onRename,
  onCopy,
  onCut,
  onPaste,
  onDelete,
  onContextMenu,
  onDropFiles,
  expandedItems,
  setExpandedItems,
  multiSelectMode,
  checkedIds,
  setCheckedIds,
  onCancel, // ← New prop from BasicTree (handleInlineCancel)
}) => {
  const [newName, setNewName] = React.useState(node.name);
  const [isOver, setIsOver] = React.useState(false);
  const expandTimeoutRef = React.useRef(null);
  const commitRef = React.useRef(false);

  // Sync newName when node.name changes (e.g., after rename from server)
  React.useEffect(() => {
    setNewName(node.name || "");
  }, [node.name]);

  const isEditing = editingId === node.term_id;
  const hasChildren = node.children && node.children.length > 0;
  const isSystem = node.term_id === -1 || node.term_id === -2 || node.term_id === -3;
  const isTopSystem = node.term_id === -1;
  const isMiddleSystem = node.term_id === -2;
  const isBottomSystem = node.term_id === -3;

  React.useEffect(() => {
    if (isEditing) {
      commitRef.current = false;
    }
  }, [isEditing]);

  const commitRename = async () => {
    if (commitRef.current) return;
    commitRef.current = true;
    const trimmed = newName.trim();
    if (!trimmed) {
      // If empty, treat as cancel
      if (onCancel) onCancel(node.term_id);
      return;
    }

    // Only call API for existing folders (not temp new ones)
    if (typeof node.term_id === "number" && !node.isTemp) {
      const data = await WP_Aioml_RenameFolder(node.term_id, trimmed);
      console.log("Rename API response:", data);
    }

    onRename(node.term_id, trimmed);
    setEditingId(null);
  };

  const handleDragOver = (event) => {
    event.preventDefault();
    setIsOver(true);

    if (hasChildren && !expandTimeoutRef.current) {
      expandTimeoutRef.current = setTimeout(() => {
        expandTimeoutRef.current = null;
        const key = String(node.term_id);
        if (!expandedItems.includes(key)) {
          setExpandedItems((prev) => {
            const updated = [...prev, key];
            localStorage.setItem("Expanedfolders", JSON.stringify(updated));
            return updated;
          });
        }
      }, 500);
    }
  };

  const clearExpandTimeout = () => {
    if (expandTimeoutRef.current) {
      clearTimeout(expandTimeoutRef.current);
      expandTimeoutRef.current = null;
    }
  };

  const handleDragLeave = (event) => {
    event.preventDefault();
    setIsOver(false);
    clearExpandTimeout();
  };

  const handleDrop = (event) => {
    event.preventDefault();
    setIsOver(false);
    clearExpandTimeout();

    const wpData = event.dataTransfer.getData("application/json");
    if (wpData) {
      try {
        const { attachmentIds } = JSON.parse(wpData);
        if (attachmentIds && onDropFiles) {
          onDropFiles(node, { attachmentIds }, event);
        }
        return;
      } catch (e) {
        console.warn("Invalid WP drag payload", e);
      }
    }

    if (event.dataTransfer.files?.length > 0) {
      const files = Array.from(event.dataTransfer.files);
      console.log("Dropped OS files:", files);
      if (onDropFiles) {
        onDropFiles(node, { files }, event);
      }
    }
  };

  React.useEffect(() => {
    return () => clearExpandTimeout();
  }, []);

  const isChecked = checkedIds.includes(node.term_id);

  const handleCheckChange = (e) => {
    e.stopPropagation();
    const checked = e.target.checked;
    setCheckedIds((prev) =>
      checked
        ? prev.includes(node.term_id) ? prev : [...prev, node.term_id]
        : prev.filter((id) => id !== node.term_id)
    );
  };

  return (
    <TreeItem
      itemId={String(node.term_id)}

      sx={{
        // Selection/Merging logic for Special Folders remains below

        // Selection/Merging logic for Special Folders remains below

        // Single Box Logic: Adjust margins to merge -1 and -2
        ...(isTopSystem && { margin: 0, marginBottom: "4px", zIndex: 1, position: "relative" }),
        ...(isMiddleSystem && {
          margin: 0,
          marginTop: "0px",
          position: "relative",
        }),
        ...(isBottomSystem && {
          margin: 0,
          marginTop: "0px",
          position: "relative",
        }),

        // Standard gap for others
        ...(!isSystem && { mb: "1px" }),

        // Hide expand icon for All Media Files since it's a leaf now
        "& .MuiTreeItem-iconContainer": {
          display: isSystem ? "none" : "flex",
          width: isSystem ? 0 : "24px", // Fix width to align folders without children
          justifyContent: "center",
          mr: isSystem ? 0 : 0.5,
        },
        // Override padding and background for System Folders to prevent highlight leaking
        ...(isSystem && {
          "& > .MuiTreeItem-content": {
            padding: 0,
            backgroundColor: "transparent !important",
          },
          "& > .MuiTreeItem-content.Mui-selected": {
            backgroundColor: "transparent !important",
          },
          "& > .MuiTreeItem-content:hover": {
            backgroundColor: "transparent !important",
          }
        }),
        // Drag over styling for custom folders
        ...(!isSystem && isOver && {
          "& > .MuiTreeItem-content": {
            backgroundColor: "#c2ccfc !important",
          }
        })
      }}
      label={
        < Box
          sx={{
            ...LabelStyle,
            display: "flex",
            alignItems: "center",
            borderRadius: aiomlTokens.radius,
            boxSizing: "border-box",
            transition: "background 0.15s ease, color 0.15s ease, border-color 0.15s ease",
            // Special styling for System folders
            ...(isSystem && {
              ...systemFolderLabelSx(selectedId === node.term_id),

              // If it's All Media Files (Top)
              ...(isTopSystem && {
                borderRadius: `${aiomlTokens.radius} ${aiomlTokens.radius} 0 0`,
                marginBottom: 0,
              }),

              // Middle item (no rounded corners)
              ...(isMiddleSystem && {
                borderRadius: 0,
                marginBottom: "0px",
                borderTop: "none",
              }),

              // Bottom item
              ...(isBottomSystem && {
                borderRadius: `0 0 ${aiomlTokens.radius} ${aiomlTokens.radius}`,
                marginBottom: "0px",
                borderTop: "none",
              }),
            }),
            // Default hover/select for others, or override if -1
            ...(!isSystem && {
              backgroundColor: "transparent",
            }),
            // Drag over styling for system folders
            ...(isSystem && isOver && {
              backgroundColor: "#c2ccfc !important",
            }),
          }}
          onClick={(e) => {
            e.stopPropagation();
            if (isEditing) return;
            setSelectedId(node.term_id);
            console.log("Selected Folder:", node.term_id);
            localStorage.setItem("selectedFolder", node.term_id);
            filterMediaLibraryByFolder(node.term_id, setSelectedId);
          }}
          onMouseDown={(e) => {
            if (isEditing) e.stopPropagation();
          }}
          onContextMenu={(e) => onContextMenu(e, node.term_id)}
          onDragOver={handleDragOver}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
        >
          {/* Multi-select checkbox */}
          {
            multiSelectMode && !isSystem && (
              <Checkbox
                checked={isChecked}
                onChange={handleCheckChange}
                onClick={(e) => e.stopPropagation()}
                size="small"
                sx={{
                  padding: 0,
                  marginRight: "6px",
                  "&.Mui-checked": {
                    color: aiomlTokens.accentStrong,
                  },
                }}
              />
            )
          }

          {/* Special handling for All Media Files icon logic if needed, but standard is fine since we set color globally */}
          <FolderIcon
            className="folder-icon"
            sx={{
              color:
                selectedId === node.term_id
                  ? aiomlTokens.accentStrong
                  : aiomlTokens.accent,
              fontSize: 18,
              mr: 0.5,
            }}
          />

          {
            isEditing ? (
              <input
                autoFocus
                value={newName}
                placeholder="Enter Folder Name…"   // ← Key fix: shows placeholder!
                onChange={(e) => setNewName(e.target.value)}
                onBlur={commitRename}
                onKeyDown={(e) => {
                  e.stopPropagation();
                  if (e.key === "Enter") {
                    e.preventDefault();
                    commitRename();
                  }
                  if (e.key === "Escape") {
                    setNewName(node.name || ""); // Restore original
                    if (onCancel) onCancel(node.term_id);
                  }
                }}
                onKeyUp={(e) => {
                  e.stopPropagation();
                  if (e.key === "Enter") {
                    e.preventDefault();
                    commitRename();
                  }
                }}
                onKeyDownCapture={(e) => e.stopPropagation()}
                onFocus={(e) => e.stopPropagation()}
                style={{
                  ...InputStyle,
                  color: newName.trim() ? aiomlTokens.ink : aiomlTokens.inkMuted,
                  fontStyle: newName.trim() ? "normal" : "italic",
                  minWidth: "120px",
                }}
                onClick={(e) => e.stopPropagation()}
                onMouseDown={(e) => e.stopPropagation()}
                onMouseDownCapture={(e) => e.stopPropagation()}
              />
            ) : (
              <span
                title={node.name}
                style={{
                  marginLeft: 4,
                  whiteSpace: "nowrap",
                  overflow: "hidden",
                  textOverflow: "ellipsis",
                  flex: 1,
                  minWidth: 0,
                  display: "block"
                }}
              >
                {node.name || "Untitled Folder"}
              </span>
            )
          }
        </Box >
      }
    >
      {
        node.children?.map((child) => (
          <RenderTree
            key={child.term_id}
            node={child}
            level={level + 1}
            selectedId={selectedId}
            setSelectedId={setSelectedId}
            editingId={editingId}
            setEditingId={setEditingId}
            onRename={onRename}
            onCopy={onCopy}
            onCut={onCut}
            onPaste={onPaste}
            onDelete={onDelete}
            onContextMenu={onContextMenu}
            onDropFiles={onDropFiles}
            expandedItems={expandedItems}
            setExpandedItems={setExpandedItems}
            multiSelectMode={multiSelectMode}
            checkedIds={checkedIds}
            setCheckedIds={setCheckedIds}
            onCancel={onCancel} // ← Pass down for new folder cancel
          />
        ))
      }
    </TreeItem >
  );
};

export default React.memo(RenderTree);
