import { Menu, MenuItem } from "@mui/material";

export default function OptionMenu({
  contextMenu,
  onClose,
  onRename,
  onDelete,
  onCopy,
  onCut,
  onPaste,
  onCreateFolder, // required
}) {
  if (!contextMenu) return null;

  const { mouseX, mouseY, nodeId } = contextMenu;
  const isSpecial = nodeId === -1 || nodeId === -2 || nodeId === -3;

  return (
    <Menu
      open={!!contextMenu}
      onClose={onClose}
      anchorReference="anchorPosition"
      anchorPosition={{ top: mouseY, left: mouseX }}
    >
      <MenuItem
        onClick={() => {
          if (onCreateFolder) onCreateFolder(nodeId);
        }}
      >
        New Folder
      </MenuItem>

      {nodeId !== -1 && (
        <>
          {!isSpecial && (
            <>
              <MenuItem onClick={() => onRename(nodeId)}>Rename</MenuItem>
              <MenuItem onClick={() => onDelete(nodeId)}>Delete</MenuItem>
              <MenuItem onClick={() => onCopy(nodeId)}>Copy</MenuItem>
              <MenuItem onClick={() => onCut(nodeId)}>Cut</MenuItem>
            </>
          )}
        </>
      )}

      <MenuItem onClick={() => onPaste(nodeId)} disabled={nodeId === -2 || nodeId === -3}>
        Paste
      </MenuItem>
    </Menu>
  );
}
