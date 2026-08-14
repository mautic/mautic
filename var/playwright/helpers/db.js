import { execSync } from 'node:child_process';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../../..');

/**
 * Run a SQL query against the DDEV MariaDB instance.
 *
 * @param {string} sql
 * @returns {string}
 */
export function runSql(sql) {
  const escaped = sql.replace(/"/g, '\\"');
  return execSync(`ddev mysql -N -B -e "${escaped}"`, {
    cwd: projectRoot,
    encoding: 'utf8',
    stdio: ['pipe', 'pipe', 'pipe'],
  }).trim();
}

/**
 * @returns {number}
 */
export function getMaxHitId() {
  const value = runSql('SELECT COALESCE(MAX(id), 0) FROM page_hits');
  return Number.parseInt(value, 10) || 0;
}

/**
 * @param {number} afterId
 * @returns {Array<{ id: number, dateHit: string, dateLeft: string|null, url: string, leadId: number|null, dwellSeconds: number|null }>}
 */
export function getHitsAfter(afterId) {
  const rows = runSql(
    `SELECT id, date_hit, IFNULL(date_left, ''), url, IFNULL(lead_id, 0), IFNULL(TIMESTAMPDIFF(SECOND, date_hit, date_left), -1) FROM page_hits WHERE id > ${afterId} ORDER BY id ASC`
  );

  if (!rows) {
    return [];
  }

  return rows.split('\n').map((line) => {
    const [id, dateHit, dateLeft, url, leadId, dwellSeconds] = line.split('\t');

    return {
      id: Number.parseInt(id, 10),
      dateHit,
      dateLeft: '' === dateLeft ? null : dateLeft,
      url,
      leadId: Number.parseInt(leadId, 10) || null,
      dwellSeconds: Number.parseInt(dwellSeconds, 10),
    };
  });
}

/**
 * Poll until date_left is populated (messenger may process asynchronously).
 *
 * @param {number} hitId
 * @param {number} minDwellSeconds
 * @param {number} timeoutMs
 */
export async function waitForDateLeft(hitId, minDwellSeconds = 1, timeoutMs = 20_000) {
  const started = Date.now();

  while (Date.now() - started < timeoutMs) {
    const row = runSql(
      `SELECT IFNULL(date_left, ''), IFNULL(TIMESTAMPDIFF(SECOND, date_hit, date_left), -1) FROM page_hits WHERE id = ${hitId}`
    );
    const [dateLeft, dwell] = row.split('\t');

    if (dateLeft && Number.parseInt(dwell, 10) >= minDwellSeconds) {
      return { dateLeft, dwellSeconds: Number.parseInt(dwell, 10) };
    }

    await new Promise((resolve) => setTimeout(resolve, 500));
  }

  throw new Error(`Hit #${hitId} still has NULL date_left after ${timeoutMs}ms`);
}
