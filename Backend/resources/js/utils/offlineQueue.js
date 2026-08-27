import { openDB } from "idb";

const DB_NAME = "ampare-offline-queue";
const STORE_NAME = "pending-requests";

const DB_VERSION = 2;

function getDb() {
  return openDB(DB_NAME, DB_VERSION, {
    upgrade(db, oldVersion, _newVersion, transaction) {
      let store;

      if (!db.objectStoreNames.contains(STORE_NAME)) {
        store = db.createObjectStore(STORE_NAME, { keyPath: "idempotencyKey" });
        store.createIndex("createdAt", "createdAt");
      } else {
        store = transaction.objectStore(STORE_NAME);
      }

      if (oldVersion < 2 && !store.indexNames.contains("userId")) {
        store.createIndex("userId", "userId");
      }
    },
  });
}

/**
 * @param {{ idempotencyKey: string, url: string, method: string, data: object, isFormData: boolean }} entry
 * @param {string|number} userId  
 */
export async function enqueueRequest(entry, userId) {
  if (userId === undefined || userId === null) {
    throw new Error(
      "enqueueRequest: userId is required to scope the request to its owner.",
    );
  }

  const db = await getDb();
  await db.put(STORE_NAME, {
    ...entry,
    userId: String(userId),
    createdAt: Date.now(),
    attempts: 0,
  });
}

export async function listQueuedRequests() {
  const db = await getDb();
  return db.getAllFromIndex(STORE_NAME, "createdAt");
}

export async function listQueuedRequestsForUser(userId) {
  const db = await getDb();
  return db.getAllFromIndex(STORE_NAME, "userId", String(userId));
}

export async function removeQueuedRequest(idempotencyKey) {
  const db = await getDb();
  await db.delete(STORE_NAME, idempotencyKey);
}

export async function updateAttempts(idempotencyKey, attempts) {
  const db = await getDb();
  const record = await db.get(STORE_NAME, idempotencyKey);
  if (record) {
    record.attempts = attempts;
    await db.put(STORE_NAME, record);
  }
}

export async function queueCount() {
  const db = await getDb();
  return db.count(STORE_NAME);
}

export async function queueCountForUser(userId) {
  const db = await getDb();
  return db.countFromIndex(STORE_NAME, "userId", String(userId));
}

export async function clearQueueForUser(userId) {
  const db = await getDb();
  const tx = db.transaction(STORE_NAME, "readwrite");
  const index = tx.store.index("userId");
  let cursor = await index.openCursor(IDBKeyRange.only(String(userId)));

  while (cursor) {
    await cursor.delete();
    cursor = await cursor.continue();
  }

  await tx.done;
}
