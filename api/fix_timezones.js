import fs from "fs";

const scheduledFile = "./scheduled_emails.json";

if (!fs.existsSync(scheduledFile)) {
  console.log("No scheduled_emails.json found.");
  process.exit(0);
}

const scheduled = JSON.parse(fs.readFileSync(scheduledFile, "utf8"));

for (const email of scheduled) {
  if (!email.timezone || email.timezone.trim() === "") {
    email.timezone = "Asia/Manila"; // default fallback
    console.log(`Added timezone Asia/Manila to email for ${email.to}`);
  }
}

fs.writeFileSync(scheduledFile, JSON.stringify(scheduled, null, 2));
console.log("All entries updated with timezone.");
