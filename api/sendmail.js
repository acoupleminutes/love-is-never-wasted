import nodemailer from "nodemailer";
import fs from "fs";
import formidable from "formidable";
import { DateTime } from "luxon";

export default async function handler(req, res) {
  if (req.method !== "POST") {
    return res.status(405).send("Method Not Allowed");
  }

  const form = formidable({ multiples: false });
  form.parse(req, async (err, fields, files) => {
    if (err) return res.status(500).send("Form parse error");

    const { to, subject, message, send_time, timezone = "UTC" } = fields;

    let attachmentPath = null;
    let attachmentName = null;
    if (files.attachment) {
      const file = files.attachment;
      attachmentName = file.originalFilename;
      attachmentPath = file.filepath;
    }

    // ✅ Transporter setup with Gmail + App Password
    const transporter = nodemailer.createTransport({
      service: "gmail",
      auth: {
        user: process.env.GMAIL_USER,          // must be set in Vercel env
        pass: process.env.GMAIL_APP_PASSWORD,  // must be set in Vercel env
      },
    });

    // ✅ Immediate send
    if (!send_time) {
      try {
        await transporter.sendMail({
          from: process.env.GMAIL_USER, // REQUIRED to avoid parse error
          to,
          subject,
          text: message,
          attachments: attachmentPath
            ? [{ path: attachmentPath, filename: attachmentName }]
            : [],
        });
        res.status(200).send("Your letter was sent successfully!");
        if (attachmentPath) fs.unlinkSync(attachmentPath); // cleanup temp file
      } catch (err) {
        console.error("Send error:", err);
        res.status(500).send("Message could not be sent. Error: " + err.message);
      }
    } else {
      // ✅ Scheduled send
      const dt = DateTime.fromFormat(send_time, "yyyy-MM-dd'T'HH:mm", { zone: timezone });
      if (!dt.isValid) return res.status(400).send("Invalid date format.");
      const send_time_utc = dt.setZone("UTC").toFormat("yyyy-MM-dd'T'HH:mm");

      const scheduledFile = "./scheduled_emails.json";
      let scheduled = [];
      if (fs.existsSync(scheduledFile)) {
        scheduled = JSON.parse(fs.readFileSync(scheduledFile, "utf8"));
      }

      scheduled.push({
        to,
        subject,
        message,
        send_time: send_time_utc,
        status: "pending",
        attachment: attachmentPath,
        attachment_name: attachmentName,
        timezone,
      });

      fs.writeFileSync(scheduledFile, JSON.stringify(scheduled, null, 2));
      res.status(200).send(
        `Your letter has been scheduled for ${send_time} (${timezone}), stored as ${send_time_utc} UTC!`
      );
    }
  });
}
