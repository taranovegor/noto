/// <reference types="vite/client" />

declare module '@vitejs/plugin-react/preamble' {}

declare namespace React {
  interface CSSProperties {
    [key: `--${string}`]: string | number;
  }
}
