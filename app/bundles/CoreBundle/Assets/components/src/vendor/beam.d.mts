export type BeamTheme = { strokeOpacity: number; innerOpacity: number; bloomOpacity: number; innerShadow: string; saturation: number };
export const beamThemes: { md: Record<'light' | 'dark', BeamTheme> };
export function beamCSS(options: BeamTheme & { id: string; borderRadius: number; borderWidth: number; duration: number; colorVariant: 'colorful' | 'mono' | 'ocean' | 'sunset'; staticColors: boolean; brightness: number; hueRange: number; theme: 'light' | 'dark' }): string;
