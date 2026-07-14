import React from "react";
import {
  Box,
  IconButton,
  Tooltip,
  Typography,
  TextField,
  Divider,
  Button,
} from "@mui/material";

import BasicTree from "./folder-tree";

import DriveFileRenameOutlineIcon from "@mui/icons-material/DriveFileRenameOutline";
import DeleteIcon from "@mui/icons-material/DeleteOutline"; // Cleaner outline icon
import ContentCopyIcon from "@mui/icons-material/ContentCopy";
import ContentCutIcon from "@mui/icons-material/ContentCut";
import ContentPasteIcon from "@mui/icons-material/ContentPaste";
import SwapVertIcon from "@mui/icons-material/SwapVert";

import FolderIcon from "@mui/icons-material/Folder";
import InboxIcon from "@mui/icons-material/Inbox"; // Alternative for "All Media"
// import CreateNewFolderIcon from "@mui/icons-material/CreateNewFolder";
import AddIcon from "@mui/icons-material/Add";
import CheckBoxOutlineBlankIcon from "@mui/icons-material/CheckBoxOutlineBlank";
import CheckBoxIcon from "@mui/icons-material/CheckBox";
import { filterMediaLibraryByFolder } from "./fetch/fetch_termid";
import { CreateNewFolder } from "./fetch/createfolder";

import {
  MainBox,
  AddIcons,
  H1_box,
  H1_styles,
  treeBox,
  NewFolderButtonSx,
  aiomlTokens,
  HeaderIconBox,
  HeaderTitleContainer,
  HeaderSubtitleStyles,
} from "./treeStyles";

export default function AppTree() {
  const treeRef = React.useRef(null);

  const [showInput, setShowInput] = React.useState(false);
  const [newFolderName, setNewFolderName] = React.useState("");

  const [selectedId, setSelectedId] = React.useState(null);
  const [hasClipboard, setHasClipboard] = React.useState(false);
  const [sortLabel, setSortLabel] = React.useState("asc");

  // Multi-select state
  const [multiSelectMode, setMultiSelectMode] = React.useState(false);
  const [checkedIds, setCheckedIds] = React.useState([]);

  const handleBackgroundClick = (e) => {
    setSelectedId(null);
    localStorage.removeItem("selectedFolder");
  };

  const handleToggleSort = () => {
    if (!treeRef.current?.toggleSort) return;
    const newOrder = treeRef.current.toggleSort();
    if (newOrder) setSortLabel(newOrder);
  };

  // Treat "All Folders" (-1) as a valid selection to enable toolbar icons
  const hasSingleSelection = !!selectedId;
  const hasMultiSelection = checkedIds.length > 0;

  const resetMultiSelect = () => {
    setMultiSelectMode(false);
    setCheckedIds([]);
  };

  return (
    <Box
      sx={MainBox}
      onClick={(e) => {
        if (
          e.target.closest("button") ||
          e.target.closest(".toolbar") ||
          e.target.closest(".MuiTreeItem-content") ||
          e.target.closest(".MuiTreeItem-root") ||
          e.target.closest("input") ||
          e.target.closest(".MuiCheckbox-root")
        ) {
          return;
        }
        handleBackgroundClick(e);
      }}
    >
      {/* Header Section */}
      <Box sx={H1_box}>
        <Box sx={{ display: 'flex', alignItems: 'center', gap: '10px', flex: 1 }}>
          <Box sx={HeaderIconBox}>ML</Box>
          <Box sx={HeaderTitleContainer}>
            <Typography variant="subtitle2" sx={H1_styles}>
              Media Library
            </Typography>
            <Typography variant="caption" sx={HeaderSubtitleStyles}>
              Manager for WordPress
            </Typography>
          </Box>
        </Box>

        <Button
          variant="contained"
          size="small"
          startIcon={<AddIcon sx={{ fontSize: 14 }} />}
          onClick={() => {
            setShowInput(true);
          }}
          sx={NewFolderButtonSx}
        >
          New Folder
        </Button>
      </Box>

      {/* Input Field for New Folder */}
      {showInput && (
        <Box sx={{ mb: 2, mt: "12px" }}>
          <TextField
            fullWidth
            size="small"
            autoFocus
            placeholder="Folder name..."
            value={newFolderName}
            onChange={(e) => setNewFolderName(e.target.value)}
            onKeyDown={async (e) => {
              if (e.key === "Enter") {
                if (newFolderName.trim()) {
                  try {
                    const data = await CreateNewFolder(newFolderName.trim(), 0);
                    if (data && data.success === true) {
                      if (treeRef.current?.addFolder) {
                        treeRef.current.addFolder({
                          term_id: Number(data.data.term_id),
                          name: newFolderName.trim(),
                          parent: 0,
                          id: Number(data.data.term_id)
                        });
                      }
                    } else {
                      alert(data?.data?.message || data?.message || "Failed to create folder");
                    }
                  } catch (error) {
                    console.error("Error creating folder:", error);
                  }
                }
                setShowInput(false);
                setNewFolderName("");
              } else if (e.key === "Escape") {
                setShowInput(false);
                setNewFolderName("");
              }
            }}
            onBlur={() => setShowInput(false)}
            onClick={(e) => e.stopPropagation()}
            sx={{
              "& .MuiInputBase-root": {
                fontSize: "13px",
                fontFamily: aiomlTokens.fontSans,
                backgroundColor: aiomlTokens.panel,
                borderRadius: aiomlTokens.radius,
              },
              "& .MuiInputBase-input:focus": {
                boxShadow: "none !important",
                outline: "none !important",
                borderColor: "transparent !important",
              },
              "& .MuiOutlinedInput-notchedOutline": {
                borderColor: aiomlTokens.hairline,
              },
              "& .MuiOutlinedInput-root:hover .MuiOutlinedInput-notchedOutline": {
                borderColor: aiomlTokens.hairline,
              },
              "& .MuiOutlinedInput-root.Mui-focused .MuiOutlinedInput-notchedOutline": {
                borderColor: aiomlTokens.accent,
                borderWidth: "1px",
              },
              "& .MuiOutlinedInput-root.Mui-focused": {
                boxShadow: "none",
              },
            }}
          />
        </Box>
      )}



      {/* Toolbar - Actions */}
      <Box className="toolbar" sx={AddIcons} onClick={(e) => e.stopPropagation()}>
        <Tooltip title="Select Multiple">
          <IconButton
            size="small"
            disabled={!hasSingleSelection}
            onClick={() => {
              setMultiSelectMode((p) => !p);
              setCheckedIds([]);
            }}
            color={multiSelectMode ? "primary" : "default"}
          >
            {multiSelectMode ? <CheckBoxIcon fontSize="small" /> : <CheckBoxOutlineBlankIcon fontSize="small" />}
          </IconButton>
        </Tooltip>

        <Tooltip title="Delete">
          <IconButton
            size="small"
            disabled={
              !hasSingleSelection ||
              ((selectedId === -1 || selectedId === -2 || selectedId === -3) && !multiSelectMode) ||
              (multiSelectMode ? !hasMultiSelection : false)
            }
            onClick={() => {
              if (multiSelectMode) {
                treeRef.current.deleteMultiple(checkedIds);
                resetMultiSelect();
              } else {
                treeRef.current.deleteSelected();
              }
            }}
          >
            <DeleteIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title="Rename">
          <IconButton
            size="small"
            disabled={!hasSingleSelection || multiSelectMode || selectedId === -1 || selectedId === -2 || selectedId === -3}
            onClick={() => treeRef.current.renameSelected()}
          >
            <DriveFileRenameOutlineIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title="Copy">
          <IconButton
            size="small"
            disabled={!hasSingleSelection || (multiSelectMode ? !hasMultiSelection : false) || selectedId === -2 || selectedId === -3}
            onClick={() => {
              if (multiSelectMode) {
                treeRef.current.copyMultiple(checkedIds);
                resetMultiSelect();
              } else {
                treeRef.current.copySelected();
              }
            }}
          >
            <ContentCopyIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title="Paste">
          <IconButton
            size="small"
            disabled={!hasSingleSelection || multiSelectMode || !hasClipboard || selectedId === -2 || selectedId === -3}
            onClick={() => treeRef.current.pasteSelected()}
          >
            <ContentPasteIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title="Cut">
          <IconButton
            size="small"
            disabled={!hasSingleSelection || (multiSelectMode ? !hasMultiSelection : false) || selectedId === -2 || selectedId === -3}
            onClick={() => {
              if (multiSelectMode) {
                treeRef.current.cutMultiple(checkedIds);
                resetMultiSelect();
              } else {
                treeRef.current.cutSelected();
              }
            }}
          >
            <ContentCutIcon fontSize="small" />
          </IconButton>
        </Tooltip>

        <Tooltip title={`Sort ${sortLabel === "asc" ? "Descending" : "Ascending"}`}>
          <IconButton
            size="small"
            disabled={!hasSingleSelection}
            onClick={handleToggleSort}
          >
            <SwapVertIcon fontSize="small" />
          </IconButton>
        </Tooltip>
      </Box>

      {/* Tree Content */}
      <Box className="tree-container" sx={treeBox}>
        <BasicTree
          ref={treeRef}
          selectedId={selectedId}
          setSelectedId={setSelectedId}
          onClipboardChange={setHasClipboard}
          multiSelectMode={multiSelectMode}
          setMultiSelectMode={setMultiSelectMode}
          checkedIds={checkedIds}
          setCheckedIds={setCheckedIds}
        />
      </Box>
    </Box>
  );
}
