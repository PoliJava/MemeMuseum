/** Zero-pads a post/comment ID to 9 digits for display, e.g. 42 → "000000042" */
export function formatPostNo(id: number): string {
  return String(id).padStart(9, '0')
}
