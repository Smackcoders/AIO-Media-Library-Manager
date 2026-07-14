/* Audie Data Migrator premium design tokens (sidebar) */
export const aiomlTokens = {
  accent: "#3d4fdb",
  accentStrong: "#2f3cb0",
  accent50: "#eef1ff",
  accentSelected: "#bdc6f8ff",
  panel: "#ffffff",
  hairline: "#e2e8f0",
  hairlineSoft: "#e9eef5",
  ink: "#0f172a",
  inkMuted: "#64748b",
  slate50: "#f8fafc",
  shadowXs: "0 1px 0 rgba(15, 23, 42, .04)",
  fontSans:
    "'Inter Tight', 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif",
  radiusSm: "6px",
  radius: "8px",
  radiusLg: "14px",
};

export const treeBox = {
  flex: 1,
  overflowY: "auto",
  overflowX: "hidden",
  paddingTop: "2px",
  px: "4px",
  "&::-webkit-scrollbar": {
    width: "6px",
  },
  "&::-webkit-scrollbar-track": {
    background: "transparent",
  },
  "&::-webkit-scrollbar-thumb": {
    background: aiomlTokens.hairline,
    borderRadius: "3px",
  },
  "&::-webkit-scrollbar-thumb:hover": {
    background: aiomlTokens.inkMuted,
  },
};

export const treeStyles = {
  "--TreeItem-indent": "18px",
  fontFamily: aiomlTokens.fontSans,
  "& .MuiTreeItem-root": {
    margin: "0 0 2px 0",
  },
  "& .MuiTreeItem-content": {
    borderRadius: aiomlTokens.radius,
    paddingTop: "8px",
    paddingBottom: "8px",
    paddingRight: "10px",
    paddingLeft: "8px !important",
    border: "1px solid transparent",
    transition: "background 0.15s ease, color 0.15s ease",
  },
  "& .MuiTreeItem-label": {
    fontSize: "13.5px",
    fontFamily: aiomlTokens.fontSans,
    fontWeight: 500,
    color: aiomlTokens.ink,
    lineHeight: 1.2,
  },
  "& .MuiTreeItem-group, & .MuiCollapse-root": {
    marginLeft: "20px !important",
    paddingLeft: "0 !important",
    borderLeft: `1px solid ${aiomlTokens.hairlineSoft}`,
  },
  "& .MuiTreeItem-content.Mui-selected": {
    backgroundColor: `${aiomlTokens.accentSelected} !important`,
    color: `${aiomlTokens.accentStrong} !important`,
    boxShadow: "none",
  },
  "& .MuiTreeItem-content.Mui-selected .MuiTreeItem-label": {
    color: `${aiomlTokens.accentStrong} !important`,
    fontWeight: 600,
  },
  "& .MuiTreeItem-content:hover": {
    backgroundColor: aiomlTokens.slate50,
    color: aiomlTokens.ink,
  },
  "& .MuiTreeItem-content.Mui-selected:hover": {
    backgroundColor: `${aiomlTokens.accentSelected} !important`,
    color: `${aiomlTokens.accentStrong} !important`,
  },
  "& .MuiTreeItem-content .folder-icon": {
    color: aiomlTokens.accent,
    fontSize: 18,
    marginRight: "6px",
    transition: "color 0.15s ease",
  },
  "& .MuiTreeItem-content:hover .folder-icon": {
    color: aiomlTokens.accentStrong,
  },
  "& .MuiTreeItem-content.Mui-selected .folder-icon": {
    color: `${aiomlTokens.accentStrong} !important`,
  },
};

export const DragOverLay = {
  padding: "8px 12px",
  background: aiomlTokens.panel,
  border: `1px solid ${aiomlTokens.hairline}`,
  borderRadius: aiomlTokens.radius,
  boxShadow: "0 4px 14px -6px rgba(15, 23, 42, .12), 0 2px 4px -2px rgba(15, 23, 42, .06)",
  display: "flex",
  flexDirection: "column",
  gap: 6,
  zIndex: 9999,
};

export const LabelStyle = {
  display: "flex",
  alignItems: "center",
  gap: "8px",
  width: "100%",
  userSelect: "none",
};

export const MainBox = {
  width: "100%",
  height: "100%",
  bgcolor: aiomlTokens.panel,
  padding: "16px 12px 14px",
  display: "flex",
  flexDirection: "column",
  boxSizing: "border-box",
  fontFamily: aiomlTokens.fontSans,
};

export const TreeDotMenu = {
  height: "6px",
  opacity: "0.8",
  ml: "auto",
  display: "flex",
  alignItems: "center",
};

export const AddIcons = {
  display: "flex",
  alignItems: "center",
  justifyContent: "flex-start",
  gap: 0.5,
  whiteSpace: "nowrap",
  padding: "10px 4px",
  borderTop: `1px solid ${aiomlTokens.hairline}`,
  borderBottom: `1px solid ${aiomlTokens.hairline}`,
  margin: "12px 0",

  "& .MuiIconButton-root": {
    padding: "6px",
    color: aiomlTokens.inkMuted,
    transition: "background 0.15s ease, color 0.15s ease",
    borderRadius: aiomlTokens.radius,
  },

  "& .MuiIconButton-root:hover": {
    backgroundColor: aiomlTokens.slate50,
    color: aiomlTokens.accentStrong,
  },

  "& .MuiIconButton-root.Mui-disabled": {
    color: aiomlTokens.hairline,
  },
};

export const InputStyle = {
  border: `1px solid ${aiomlTokens.hairline}`,
  padding: "8px 10px",
  fontSize: "13px",
  borderRadius: aiomlTokens.radius,
  width: "100%",
  backgroundColor: aiomlTokens.panel,
  color: aiomlTokens.ink,
  fontFamily: aiomlTokens.fontSans,
  boxSizing: "border-box",
  transition: "border-color 0.15s ease, box-shadow 0.15s ease",
};

export const inputFocusStyle = {
  borderColor: aiomlTokens.accent,
  boxShadow: `0 0 0 3px rgba(61, 79, 219, 0.1)`,
  outline: "none",
};

export const HeaderIconBox = {
  width: "36px",
  height: "36px",
  background: "linear-gradient(135deg, #7c3aed 0%, #c026d3 100%)",
  borderRadius: "8px",
  display: "flex",
  alignItems: "center",
  justifyContent: "center",
  color: "#fff",
  fontWeight: 700,
  fontSize: "17px",
  fontFamily: aiomlTokens.fontSans,
  flexShrink: 0,
};

export const HeaderTitleContainer = {
  display: "flex",
  flexDirection: "column",
  justifyContent: "center",
  flexGrow: 1,
};

export const HeaderSubtitleStyles = {
  margin: 0,
  fontSize: "12px",
  color: aiomlTokens.inkMuted,
  fontWeight: 500,
  lineHeight: 1.2,
  fontFamily: aiomlTokens.fontSans,
};

export const H1_box = {
  pb: "12px",
  mb: "4px",
  display: "flex",
  flexDirection: "column",
  alignItems: "stretch",
  gap: "12px",
};

export const H1_styles = {
  margin: 0,
  fontSize: "16px",
  color: aiomlTokens.ink,
  fontWeight: 800,
  letterSpacing: "-0.01em",
  lineHeight: 1.1,
  fontFamily: aiomlTokens.fontSans,
};

export const NewFolderButtonSx = {
  textTransform: "none",
  fontFamily: aiomlTokens.fontSans,
  fontWeight: 600,
  fontSize: "13.5px",
  backgroundColor: aiomlTokens.accent,
  color: "#fff",
  border: `1px solid ${aiomlTokens.accent}`,
  borderRadius: aiomlTokens.radius,
  boxShadow: aiomlTokens.shadowXs,
  "&:hover": {
    backgroundColor: aiomlTokens.accentStrong,
    borderColor: aiomlTokens.accentStrong,
    boxShadow: aiomlTokens.shadowXs,
  },
  width: "100%",
  padding: "8px 14px",
  lineHeight: 1.2,
};

export const SectionLabelSx = {
  px: "4px",
  pb: "6px",
  pt: "2px",
  mb: "8px",
  fontWeight: 700,
  color: aiomlTokens.inkMuted,
  display: "block",
  fontSize: "11px",
  textTransform: "uppercase",
  letterSpacing: "0.06em",
  fontFamily: aiomlTokens.fontSans,
};

export const SectionDividerSx = {
  borderColor: aiomlTokens.hairline,
  opacity: 1,
};

export const CreateFolder_btn = {
  textTransform: "none",
  width: "100%",
  backgroundColor: aiomlTokens.accent,
  color: "#fff",
};

export const sort_btn = {
  textTransform: "none",
  width: "100%",
  border: `1px solid ${aiomlTokens.hairline}`,
  borderRadius: aiomlTokens.radius,
};

export const width100 = {
  width: "100%",
};

export const systemFolderLabelSx = (selected) => ({
  border: "1px solid transparent",
  padding: "8px 10px",
  backgroundColor: selected ? aiomlTokens.accentSelected : "transparent",
  color: selected ? aiomlTokens.accentStrong : aiomlTokens.ink,
  borderRadius: aiomlTokens.radius,
  "&:hover": {
    backgroundColor: selected ? aiomlTokens.accentSelected : aiomlTokens.slate50,
  },
});
