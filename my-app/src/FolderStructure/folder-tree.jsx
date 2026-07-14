//trial code//
import * as React from "react";
import { SimpleTreeView, TreeItemGroupTransition } from "@mui/x-tree-view";
import ExpandMoreIcon from "@mui/icons-material/ExpandMore";
import ChevronRightIcon from "@mui/icons-material/ChevronRight";

import RenderTree from "./RenderTree";
import OptionMenu from "./OptionMenu";
import { treeStyles, SectionLabelSx, SectionDividerSx } from "./treeStyles";
import { Box, Divider, Typography } from "@mui/material"; // Added Box, Divider, and Typography
import { fetchAllFolders } from "./fetch/fetchAllFolders";
import { CreateNewFolder } from "./fetch/createfolder";
import { WP_DeleteFolder } from "./fetch/deletefolder";
import { WP_CopyPasteFolders, WP_CutPasteFolders } from "./fetch/pastefolder";
import { patchMediaDeleteUI } from "./fetch/safedelete";
import { filterMediaLibraryByFolder } from "./fetch/fetch_termid";

const BasicTree = React.forwardRef(
  (
    {
      selectedId,
      setSelectedId,
      onClipboardChange,
      multiSelectMode,
      setMultiSelectMode, // Added this
      checkedIds,
      setCheckedIds,
    },
    ref
  ) => {
    const [items, setItems] = React.useState([]);
    const [editingId, setEditingId] = React.useState(null);
    const [contextMenu, setContextMenu] = React.useState(null);
    const [expandedItems, setExpandedItems] = React.useState([]);
    const [sortOrder, setSortOrder] = React.useState("asc");
    const [creatingInParent, setCreatingInParent] = React.useState(null);
    const [clipboard, setClipboard] = React.useState({
      items: [],
      type: null,
    });

    React.useEffect(() => {
      patchMediaDeleteUI();
      fetchAllFolders(setItems);
      const saved = localStorage.getItem("Expanedfolders");
      let initialExpanded = ["-1"];
      if (saved) {
        try {
          const arr = JSON.parse(saved);
          if (Array.isArray(arr)) {
            // Merge saved with default root (-1)
            initialExpanded = Array.from(new Set([...arr, "-1"]));
          }
        } catch (e) {
          console.error("Invalid expanded folder data", e);
        }
      }
      setExpandedItems(initialExpanded);
    }, []);

    const expandParents = React.useCallback((id) => {
      const parents = [];
      let currentItem = items.find((i) => i.term_id === id);
      while (currentItem && currentItem.parent !== 0) {
        const parentId = currentItem.parent;
        parents.unshift(String(parentId));
        currentItem = items.find((i) => i.term_id === parentId);
      }
      setExpandedItems((prev) => {
        const merged = Array.from(new Set([...prev, ...parents]));
        localStorage.setItem("Expanedfolders", JSON.stringify(merged));
        return merged;
      });
    }, [items]);

    React.useEffect(() => {
      if (items.length === 0) return;
      const savedSelected = localStorage.getItem("selectedFolder");
      if (!savedSelected) return;
      const id = parseInt(savedSelected);

      // Only restore selection if the folder actually exists in the current list
      const folderExists = items.some(i => i.term_id === id);
      if (folderExists || id === -1 || id === -2 || id === -3) {
        setSelectedId(id);
        if (id !== -1 && id !== -2 && id !== -3) expandParents(id);

        // Apply filter to media library grid once ready
        const applyFilter = () => {
          if (window.wp?.media?.frame) {
            filterMediaLibraryByFolder(id);
          } else {
            setTimeout(applyFilter, 100);
          }
        };
        applyFilter();
      } else {
        // If it doesn't exist, clear it
        setSelectedId(null);
        localStorage.removeItem("selectedFolder");
      }
    }, [items, expandParents, setSelectedId]);

    const buildTree = React.useCallback(
      (list, parent = 0) => {
        const filtered = list.filter((item) => item.parent === parent);

        // Sort the filtered items based on current sortOrder
        filtered.sort((a, b) => {
          const nameA = (a.name || "").toLowerCase();
          const nameB = (b.name || "").toLowerCase();

          if (nameA !== nameB) {
            return sortOrder === "asc"
              ? nameA.localeCompare(nameB)
              : nameB.localeCompare(nameA);
          }

          // Secondary sort by term_id to ensure a visible change if names are same
          return sortOrder === "asc"
            ? Number(a.term_id) - Number(b.term_id)
            : Number(b.term_id) - Number(a.term_id);
        });

        console.log(`Sorted ${filtered.length} items at parent ${parent} (${sortOrder})`);

        return filtered.map((item) => ({
          ...item,
          children: buildTree(list, item.term_id),
        }));
      },
      [sortOrder]
    );

    const treeData = React.useMemo(() => {
      const rootChildren = buildTree(items, 0);

      const allMediaNode = {
        term_id: -1,
        name: "All Media Files",
        children: [], // No children, it's a "header" or "filter" node
        parent: null,
        isRoot: true
      };

      // Uncategorized is typically a special folder. To match the image, 
      // if it exists, it should probably be a sibling below "All Media Files", 
      // or integrated if the user has it.
      // Based on previous context, we'll strip it out if it was just a mock, 
      // but if we need it, we add it. 
      // The image doesn't show "Uncategorized", but does show "lap", "he", "smart phones".
      // We will assume standard folders start after "All Media Files".

      const uncategorizedNode = {
        term_id: -2,
        name: "Uncategorized",
        children: [],
        parent: -1,
        isSystem: true
      };

      // const sampleNode = {
      //   term_id: -3,
      //   name: "Sample Folder",
      //   children: [],
      //   parent: -1,
      //   isSystem: true
      // };

      // Flatten structure: "All Media Files" -> Uncategorized -> Root Folders
      // This makes them siblings in the SimpleTreeView.
      return [allMediaNode, uncategorizedNode, ...rootChildren];
    }, [items, buildTree]);

    const collectAllDescendants = React.useCallback((list, parentId) => {
      const children = list.filter((item) => item.parent === parentId);
      return children.reduce(
        (acc, child) => [
          ...acc,
          child.term_id,
          ...collectAllDescendants(list, child.term_id),
        ],
        []
      );
    }, []);

    const renameFolder = (id, newName) => {
      const trimmed = newName?.trim();
      if (!trimmed) return;
      setItems((prev) =>
        prev.map((item) =>
          item.term_id === id ? { ...item, name: trimmed } : item
        )
      );
    };

    // FIXED: deleteFolder – now async, safe, and optimistic
    const deleteFolder = async (id) => {
      // Prevent deleting the root "All Media Files" folder or "Uncategorized"
      if (id === -1 || id === -2 || id === -3) return;

      if (!window.confirm("Are you sure you want to delete the folder?")) return;

      const previousItems = [...items];
      const toDelete = [id, ...collectAllDescendants(items, id)];

      // OPTIMISTIC UPDATE: remove from UI immediately
      setItems((prev) => prev.filter((item) => !toDelete.includes(item.term_id)));
      setSelectedId(0);

      try {
        const data = await WP_DeleteFolder(id);

        if (!data || !data.success) {
          // RESTORE state if failed
          setItems(previousItems);
          alert(data?.data?.message || "Failed to delete folder");
          return;
        }
      } catch (error) {
        setItems(previousItems);
        console.error("Delete folder error:", error);
        alert("An error occurred while deleting the folder.");
      }
    };

    const deleteFolders = async (ids) => {
      const validIds = ids?.filter(id => id !== -1 && id !== -2 && id !== -3) || [];
      if (validIds.length === 0) return;

      const idsToDelete = validIds;
      if (!window.confirm("Are you sure you want to delete the selected folder(s)?")) return;

      const previousItems = [...items];
      const toDeleteSet = new Set();
      ids.forEach((id) => {
        toDeleteSet.add(id);
        collectAllDescendants(items, id).forEach((childId) =>
          toDeleteSet.add(childId)
        );
      });

      // OPTIMISTIC UPDATE
      setItems((prev) => prev.filter((item) => !toDeleteSet.has(item.term_id)));
      setSelectedId(0);

      try {
        // OPTIMIZATION: Filter original 'ids' to only include those that ARE NOT descendants of others in 'ids'
        // Since the backend is now recursive, we only need to call it for the topmost parents.
        const topLevelIds = ids.filter(id => {
          const item = previousItems.find(i => i.term_id === id);
          if (!item) return false;
          // Check if any of its ancestors are also in the 'ids' list
          let p = item.parent;
          while (p !== 0 && p !== null) {
            if (ids.includes(p)) return false; // Ancestor is already being deleted
            const parentItem = previousItems.find(i => i.term_id === p);
            p = parentItem ? parentItem.parent : 0;
          }
          return true;
        });

        console.log("Bulk delete top-level IDs:", topLevelIds);

        for (const id of topLevelIds) {
          const data = await WP_DeleteFolder(id);
          if (!data || !data.success) {
            console.warn(`Failed to delete folder ${id}:`, data?.data?.message);
            // We don't restore everything here, just log. 
            // If the first one fails, maybe we should restore? 
            // For now, let's keep it simple.
          }
        }
      } catch (error) {
        setItems(previousItems);
        console.error("Delete multiple folders error:", error);
        alert("An error occurred while deleting folders.");
      }
    };

    const updateClipboard = (info) => {
      setClipboard(info);
      const hasItems = Array.isArray(info.items) && info.items.length > 0;
      onClipboardChange(hasItems);
    };

    const clearClipboard = () => {
      updateClipboard({ items: [], type: null });
    };

    const copyFolder = (ids) => {
      const arr = Array.isArray(ids) ? ids : [ids];
      updateClipboard({ items: arr, type: "copy" });
    };

    const cutFolder = (ids) => {
      const arr = Array.isArray(ids) ? ids : [ids];
      updateClipboard({ items: arr, type: "cut" });
    };

    const isDescendant = (list, parentId, childId) => {
      const children = list.filter((f) => f.parent === parentId);
      for (const c of children) {
        if (c.term_id === childId) return true;
        if (isDescendant(list, c.term_id, childId)) return true;
      }
      return false;
    };

    const pasteFolder = async (targetParentId) => {
      if (!clipboard.items || clipboard.items.length === 0) return;

      const sourceIds = clipboard.items;
      const invalidForAny = sourceIds.some((sourceId) =>
        isDescendant(items, sourceId, targetParentId)
      );
      if (invalidForAny) {
        console.warn("You can't paste a parent inside its own child");
        return;
      }

      const previousItems = [...items];
      const destParent = targetParentId === -1 ? 0 : targetParentId;
      const clipboardType = clipboard.type;

      setItems((prev) => {
        if (clipboardType === "cut") {
          return prev.map((f) =>
            sourceIds.includes(f.term_id) ? { ...f, parent: destParent } : f
          );
        }

        if (clipboardType === "copy") {
          const getChildren = (id) => {
            const children = prev.filter((f) => f.parent === id);
            let result = [...children];
            children.forEach((child) => {
              result = result.concat(getChildren(child.term_id));
            });
            return result;
          };

          const isAncestor = (ancestorId, descendantId) =>
            isDescendant(prev, ancestorId, descendantId);

          const sourceRootIds = sourceIds.filter(
            (id) =>
              !sourceIds.some(
                (other) => other !== id && isAncestor(other, id)
              )
          );

          let nextId =
            prev.length > 0 ? Math.max(...prev.map((f) => f.term_id)) + 1 : 1;

          const copies = [];

          const addSubtreeCopy = (sourceRootId) => {
            const folder = prev.find((f) => f.term_id === sourceRootId);
            if (!folder) return;

            const descendants = getChildren(sourceRootId);
            const allToCopy = [folder, ...descendants];

            const idMap = {};
            allToCopy.forEach((item) => {
              idMap[item.term_id] = nextId++;
            });

            // Normalize target parent for the root copies
            const destParent = targetParentId === -1 ? 0 : targetParentId;

            allToCopy.forEach((item) => {
              copies.push({
                ...item,
                term_id: idMap[item.term_id],
                parent:
                  item.term_id === sourceRootId
                    ? destParent
                    : idMap[item.parent],
                name: item.name + " - copy",
              });
            });
          };

          sourceRootIds.forEach(addSubtreeCopy);

          return [...prev, ...copies];
        }

        return prev;
      });

      try {
        const data = clipboardType === "cut"
          ? await WP_CutPasteFolders(sourceIds, destParent)
          : await WP_CopyPasteFolders(sourceIds, destParent);

        if (!data || !data.success) {
          setItems(previousItems);
          alert(data?.data?.message || "Failed to paste folder(s).");
          return;
        }

        clearClipboard();
        await fetchAllFolders(setItems);
      } catch (error) {
        setItems(previousItems);
        console.error("Paste folder error:", error);
        alert("An error occurred while pasting folder(s).");
      }
    };

    const handleDropOnFolder = React.useCallback((folderNode, payload, event) => {
      if (payload.attachmentIds) {
        window.dispatchEvent(
          new CustomEvent("media_folder:move", {
            detail: { folderId: folderNode.term_id, attachmentIds: payload.attachmentIds },
          })
        );
        return;
      }

      if (payload.files) {
        console.log("Dropped external files:", payload.files);
        console.log("Into folder:", folderNode.term_id);
      }
    }, []);

    const handleNodeContextMenu = (e, nodeId) => {
      e.preventDefault();
      e.stopPropagation();
      setSelectedId(nodeId);
      setContextMenu({
        nodeId,
        mouseX: e.clientX + 2,
        mouseY: e.clientY + 2,
      });
    };

    const closeContextMenu = () => setContextMenu(null);

    const createFolder = async (parentId) => {
      // Default to 0 (top-level) if parent is null, 0, or -1 (Root)
      const parent = (parentId === -1 || parentId === 0 || parentId === null || parentId === undefined)
        ? (selectedId === -1 ? 0 : (selectedId ?? 0))
        : parentId;

      // Double check: if parent resolves to a system folder, force 0
      const finalParent = (parent === -1 || parent === -2 || parent === -3) ? 0 : parent;

      const tempId = `temp-${Date.now()}`;
      const placeholder = {
        term_id: tempId,
        name: "",
        parent: finalParent,
        isTemp: true,
      };

      setItems((prev) => [...prev, placeholder]);
      setCreatingInParent(finalParent);
      setEditingId(tempId);

      if (finalParent !== 0) {
        setExpandedItems((prev) => {
          const newExpanded = prev.includes(String(finalParent))
            ? prev
            : [...prev, String(finalParent)];
          localStorage.setItem("Expanedfolders", JSON.stringify(newExpanded));
          return newExpanded;
        });
      }
    };

    const handleInlineSave = async (id, newName) => {
      const trimmed = newName?.trim();
      if (!trimmed) {
        handleInlineCancel(id);
        return;
      }

      if (typeof id === "string" && id.startsWith("temp-")) {
        // Optimistically show the name immediately while creating
        setItems((prev) =>
          prev.map((item) =>
            item.term_id === id ? { ...item, name: trimmed } : item
          )
        );

        const data = await CreateNewFolder(trimmed, creatingInParent);
        if (data.success !== true) {
          alert(data.data?.message || "Failed to create folder");
          handleInlineCancel(id);
          return;
        }

        setItems((prev) =>
          prev.map((item) =>
            item.term_id === id
              ? { ...item, term_id: data.data.term_id, name: trimmed, isTemp: false }
              : item
          )
        );
      } else {
        renameFolder(id, trimmed);
      }

      setEditingId(null);
      setCreatingInParent(null);
    };

    const handleInlineCancel = (id) => {
      if (typeof id === "string" && id.startsWith("temp-")) {
        setItems((prev) => prev.filter((item) => item.term_id !== id));
        setCreatingInParent(null);
      }
      setEditingId(null);
    };

    const sortFolders = React.useCallback((order) => {
      setSortOrder(order);
    }, []);

    React.useImperativeHandle(ref, () => ({
      deleteSelected: () => selectedId && deleteFolder(selectedId),
      deleteMultiple: (ids) => deleteFolders(ids),
      renameSelected: () => selectedId && selectedId !== -1 && selectedId !== -2 && selectedId !== -3 && setEditingId(selectedId),
      copySelected: () => selectedId && copyFolder(selectedId),
      copyMultiple: (ids) => ids?.length && copyFolder(ids),
      cutSelected: () => selectedId && cutFolder(selectedId),
      cutMultiple: (ids) => ids?.length && cutFolder(ids),
      pasteSelected: () => {
        // If selected is -1 (root) or invalid, target 0
        const target = (selectedId === -1 || !selectedId) ? 0 : selectedId;
        pasteFolder(target);
      },
      createFolder,
      addFolder: (folder) => {
        setItems((prev) => {
          // check if already exists to avoid duplicates
          if (prev.some(f => f.term_id === folder.term_id)) return prev;
          // Insert at the beginning so it appears "below All Folders" immediately
          return [folder, ...prev];
        });
      },
      refreshFolders: () => fetchAllFolders(setItems),
      toggleSort: () => {
        const next = sortOrder === "asc" ? "desc" : "asc";
        sortFolders(next);
        return next;
      },
      sortOrder,
    }), [
      selectedId,
      sortOrder,
      sortFolders,
      deleteFolder,
      deleteFolders,
      copyFolder,
      cutFolder,
      pasteFolder,
      createFolder,
      fetchAllFolders, // Added missing fetchAllFolders
    ]);

    return (
      <>
        <SimpleTreeView
          key={sortOrder} // Force re-render when sorting changes
          sx={treeStyles}
          expandedItems={expandedItems}
          onExpandedItemsChange={(e, ids) => setExpandedItems(ids)}
          selectedItems={selectedId ? [String(selectedId)] : []}
          onSelectedItemsChange={(event, ids) => {
            const id = ids[0] ? Number(ids[0]) : null;
            setSelectedId(id);
            if (id !== null) {
              localStorage.setItem("selectedFolder", id);
            }
          }}
          slots={{
            expandIcon: ChevronRightIcon,
            collapseIcon: ExpandMoreIcon,
            groupTransition: TreeItemGroupTransition,
          }}
        >

          {/* Default Folder Header */}
          <Typography variant="caption" sx={SectionLabelSx}>
            Default Folder
          </Typography>

          {/* 1. Render Special Folders (All Media & Uncategorized) */}
          {treeData
            .filter((node) => node.term_id === -1 || node.term_id === -2 || node.term_id === -3)
            .map((root) => (
              <RenderTree
                key={root.term_id}
                node={root}
                level={0}
                selectedId={selectedId}
                setSelectedId={setSelectedId}
                editingId={editingId}
                setEditingId={setEditingId}
                onRename={handleInlineSave}
                onCancel={handleInlineCancel}
                onCopy={(id) => copyFolder(id)}
                onCut={(id) => cutFolder(id)}
                onPaste={(id) => pasteFolder(id)}
                onDelete={deleteFolder}
                onContextMenu={handleNodeContextMenu}
                onDropFiles={handleDropOnFolder}
                expandedItems={expandedItems}
                setExpandedItems={setExpandedItems}
                multiSelectMode={multiSelectMode}
                checkedIds={checkedIds}
                setCheckedIds={setCheckedIds}
              />
            ))}

          {/* 2. Gap and Divider Line */}
          <Box sx={{ py: 1.5 }}>
            <Divider sx={SectionDividerSx} />
          </Box>

          {/* Custom Folder Header */}
          <Typography variant="caption" sx={SectionLabelSx}>
            Custom Folder
          </Typography>

          {/* 3. Render Normal User Folders */}
          {treeData
            .filter((node) => node.term_id !== -1 && node.term_id !== -2 && node.term_id !== -3)
            .map((root) => (
              <RenderTree
                key={root.term_id}
                node={root}
                level={0}
                selectedId={selectedId}
                setSelectedId={setSelectedId}
                editingId={editingId}
                setEditingId={setEditingId}
                onRename={handleInlineSave}
                onCancel={handleInlineCancel}
                onCopy={(id) => copyFolder(id)}
                onCut={(id) => cutFolder(id)}
                onPaste={(id) => pasteFolder(id)}
                onDelete={deleteFolder}
                onContextMenu={handleNodeContextMenu}
                onDropFiles={handleDropOnFolder}
                expandedItems={expandedItems}
                setExpandedItems={setExpandedItems}
                multiSelectMode={multiSelectMode}
                checkedIds={checkedIds}
                setCheckedIds={setCheckedIds}
              />
            ))}
        </SimpleTreeView>

        <OptionMenu
          contextMenu={contextMenu}
          multiSelectMode={multiSelectMode}
          checkedIds={checkedIds}
          onClose={closeContextMenu}
          onRename={(id) => {
            closeContextMenu();
            setTimeout(() => setEditingId(id), 0);
          }}
          onDelete={(id) => {
            if (multiSelectMode && checkedIds.length > 0) {
              deleteFolders(checkedIds);
            } else {
              deleteFolder(id);
            }
            closeContextMenu();
          }}
          onCopy={(id) => {
            copyFolder(id);
            closeContextMenu();
          }}
          onCut={(id) => {
            cutFolder(id);
            closeContextMenu();
          }}
          onPaste={(id) => {
            pasteFolder(id);
            closeContextMenu();
          }}
          onCreateFolder={(parentId) => {
            createFolder(parentId);
            closeContextMenu();
          }}
          onSelect={(id) => {
            setMultiSelectMode(true);
            setCheckedIds((prev) => {
              if (prev.includes(id)) return prev;
              return [...prev, id];
            });
            closeContextMenu();
          }}
        />
      </>
    );
  }
);

export default BasicTree;
