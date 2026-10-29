// Ziptastic API on Cloudflare Workers. A drop-in replacement for the old PHP/Slim version:
// same URLs, same JSON (byte for byte, including PHP's escaped slashes), same JSONP and CORS.
//
//   GET /43016                  {"country":"US","state":"OH","city":"DUBLIN"}
//   GET /43016?callback=fn      fn({"country":"US","state":"OH","city":"DUBLIN"})
//   GET /                       the landing page
//
// Errors are still HTTP 200 with an {"error": ...} body, as they always were.

import data from "./zips.js";
import landing from "./landing.html";

const ZIPS = new Map();
for (const line of data.split("\n")) {
  const [zip, country, state, city] = line.split("|");
  ZIPS.set(Number(zip), { country, state, city });
}

// PHP's is_numeric(): optional surrounding whitespace, sign, decimal, exponent. No hex.
const NUMERIC = /^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?\s*$/;
// A JSONP callback must be a plain JS name, e.g. jQuery3640581_1793087700457 or app.cb[0].
// The PHP echoed any callback verbatim, which was a reflected XSS hole.
const CALLBACK = /^[A-Za-z_$][\w$.]*(\[\d+\])?$/;

const HEADERS = {
  // What the PHP sent. Some JSONP callers load this with <script>, so keep it text/html, not JSON.
  "Content-Type": "text/html;charset=UTF-8",
  "Access-Control-Allow-Origin": "*",
  // The PHP sent max-age=600. ZIP data almost never changes, so let browsers keep answers for a day:
  // every repeat lookup they serve themselves is a Worker request that never happens.
  "Cache-Control": "max-age=86400",
};

// json_encode(): escapes "/" and anything outside ASCII.
function phpJson(value) {
  return JSON.stringify(value)
    .replace(/\//g, "\\/")
    .replace(/[\u007f-￿]/g, (c) => "\\u" + c.charCodeAt(0).toString(16).padStart(4, "0"));
}

function respond(body, callback, status = 200) {
  const json = phpJson(body);
  const text = callback && CALLBACK.test(callback) ? `${callback}(${json})` : json;
  return new Response(text, { status, headers: HEADERS });
}

export function lookup(raw) {
  if (!NUMERIC.test(raw)) return { error: "Not a valid Zip Code!" };
  // The zipcode column is an INTEGER, so SQLite matched "02134" and "2134.0" to 2134.
  const n = Number(raw.trim());
  return (Number.isInteger(n) && ZIPS.get(n)) || { error: "Zip Code not found!" };
}

export default {
  async fetch(request) {
    const url = new URL(request.url);
    if (request.method !== "GET" && request.method !== "HEAD") {
      return new Response("Method Not Allowed", { status: 405, headers: { Allow: "GET, HEAD" } });
    }
    if (url.pathname === "/") {
      return new Response(landing, { headers: { "Content-Type": "text/html;charset=UTF-8" } });
    }
    const segment = url.pathname.slice(1);
    if (segment.includes("/")) {
      return new Response("Not Found", { status: 404, headers: { "Content-Type": "text/plain" } });
    }
    return respond(lookup(decodeURIComponent(segment)), url.searchParams.get("callback"));
  },
};
