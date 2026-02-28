const API_BASE = String(import.meta.env.VITE_API_BASE_URL || '')
  .trim()
  .replace(/\/$/, '');

let settingsPromise = null;
let blocksPromise = null;

function toApiUrl(path) {
  if (!path.startsWith('/')) {
    return API_BASE ? `${API_BASE}/${path}` : `/${path}`;
  }

  return API_BASE ? `${API_BASE}${path}` : path;
}

async function fetchJson(path, fallbackValue) {
  try {
    const response = await fetch(toApiUrl(path), {
      method: 'GET',
      headers: {
        Accept: 'application/json',
      },
      cache: 'no-store',
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }

    return await response.json();
  } catch (error) {
    console.warn(`[siteContentApi] Failed to load ${path}`, error);
    return fallbackValue;
  }
}

export function fetchSettings() {
  if (!settingsPromise) {
    settingsPromise = fetchJson('/api/settings', null);
  }

  return settingsPromise;
}

export function fetchBlocks() {
  if (!blocksPromise) {
    blocksPromise = fetchJson('/api/blocks', []);
  }

  return blocksPromise;
}

export async function fetchSiteContent() {
  const [settings, blocks, doctors, services, gallery] = await Promise.all([
    fetchSettings(),
    fetchBlocks(),
    fetchJson('/api/doctors', []),
    fetchJson('/api/services', []),
    fetchJson('/api/gallery', []),
  ]);

  return {
    settings,
    blocks,
    doctors,
    services,
    gallery,
  };
}
