declare module 'crc-32' {
  const CRC32: {
    str(str: string, seed?: number): number;
    bstr(str: string, seed?: number): number;
    buf(buf: Uint8Array | number[], seed?: number): number;
  };
  export default CRC32;
}
