import CRC32 from 'crc-32';

export function endpointChecksum(endpoint: string): string {
  return (CRC32.str(endpoint, 0) >>> 0).toString();
}
