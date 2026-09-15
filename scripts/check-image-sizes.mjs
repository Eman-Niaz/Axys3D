import { readdirSync, statSync } from "node:fs";
import { join } from "node:path";

const MAX_SIZE_KB = 300;
const IMAGE_DIR = "./public/images";

function scanDir(dir) {
  let issues = [];
  for (const file of readdirSync(dir)) {
    const fullPath = join(dir, file);
    const stat = statSync(fullPath);
    if (stat.isDirectory()) {
      issues = issues.concat(scanDir(fullPath));
    } else if (/\.(png|jpe?g|webp|gif)$/i.test(file)) {
      const sizeKB = stat.size / 1024;
      if (sizeKB > MAX_SIZE_KB) {
        issues.push(`${fullPath} — ${sizeKB.toFixed(0)}KB`);
      }
    }
  }
  return issues;
}

const issues = scanDir(IMAGE_DIR);

if (issues.length > 0) {
  console.log(`⚠️  ${issues.length} image(s) larger than ${MAX_SIZE_KB}KB:\n`);
  issues.forEach((i) => console.log("  " + i));
  process.exit(1);
} else {
  console.log("✅ All images within size limit.");
}