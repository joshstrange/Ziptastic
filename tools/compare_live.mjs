// Compare the Worker's responses with the live PHP API, byte for byte.
// Usage: node tools/compare_live.mjs [base-url]   (default https://ziptasticapi.com)
import { readFileSync } from "node:fs";
import { pathToFileURL } from "node:url";
import path from "node:path";

// Load the Worker with its two text imports inlined, the way wrangler bundles them.
const dir = path.resolve(path.dirname(new URL(import.meta.url).pathname), "../worker");
const src = readFileSync(`${dir}/index.js`, "utf8")
  .replace('import data from "./zips.js";', `import data from "${pathToFileURL(`${dir}/zips.js`)}";`)
  .replace('import landing from "./landing.html";', `const landing = ${JSON.stringify(readFileSync(`${dir}/landing.html`, "utf8"))};`);
const worker = (await import("data:text/javascript;base64," + Buffer.from(src).toString("base64"))).default;

const base = process.argv[2] || "https://ziptasticapi.com";
const cases = [
  "/43016", "/90210", "/00501", "/501", "/02134", "/2134", "/37769", "/06268", "/99950", "/96201",
  "/09001", "/12345", "/00000", "/99999", "/43016.0", "/4.3016e4", "/+43016", "/abc", "/43016abc",
  "/43016?callback=jQuery36405816257560884732_1793087700457&_=1793087700458", "/abc?callback=cb",
  "/99999?callback=cb", "/43016?callback=", "/2134?callback=app.handlers[0]",
];
let bad = 0;
for (const c of cases) {
  const live = await fetch(base + c, { headers: { "User-Agent": "ziptastic-compare" } });
  const mine = await worker.fetch(new Request("https://example.test" + c));
  const [a, b] = [await live.text(), await mine.text()];
  const ok = a === b && live.status === mine.status;
  if (!ok) bad++;
  console.log(`${ok ? "ok  " : "DIFF"} ${c}\n${ok ? "" : `     live ${live.status}: ${a.slice(0, 120)}\n     mine ${mine.status}: ${b.slice(0, 120)}\n`}`.trimEnd());
}
console.log(`${cases.length - bad}/${cases.length} identical`);
process.exit(bad ? 1 : 0);
