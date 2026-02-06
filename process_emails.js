import nodemailer from "nodemailer";
import fs from "fs";

const scheduledFile = "./scheduled_emails.json";
if (!fs.existsSync(scheduledFile)) process.exit(0);

const scheduled = JSON.parse(fs.readFileSync(scheduledFile, "utf8"));
const now = new Date();

const transporter = nodemailer.createTransport({
  service: "gmail",
  auth: {
    user: process.env.GMAIL_USER,
    pass: process.env.GMAIL_APP_PASSWORD,
  },
});

for (const email of scheduled) {
  const scheduledTime = new Date(email.send_time);

  console.log(`Checking ${scheduledTime.toISOString()} against ${now.toISOString()}`);

  if (email.status === "pending" && scheduledTime <= now) {
    try {
      const mailOptions = {
        from: process.env.GMAIL_USER,
        to: email.to,
        subject: email.subject,
        text: email.message,
        attachments: email.attachment
          ? [{ path: email.attachment, filename: email.attachment_name }]
          : [],
      };

      await transporter.sendMail(mailOptions);
      email.status = "sent";
      console.log(`Sent email to ${email.to} (subject: ${email.subject})`);

      if (email.attachment && fs.existsSync(email.attachment)) {
        fs.unlinkSync(email.attachment);
        email.attachment = null;
        email.attachment_name = null;
        console.log("Deleted attachment after sending");
      }
    } catch (err) {
      email.status = "error: " + err.message;
      console.error(`Error sending to ${email.to}: ${err.message}`);
    }
  }
}

fs.writeFileSync(scheduledFile, JSON.stringify(scheduled, null, 2));
