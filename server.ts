import express from "express";
import fs from "fs";
import path from "path";

const app = express();
const PORT = 3000;

// INI ADALAH BRIDGE UNTUK PREVIEW SAJA
// Aplikasi asli Anda adalah file .php di root.
app.get("/", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "index.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, ""); 
  res.send(html);
});

app.get("/user/login", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "user/login.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/dashboard", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/dashboard.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/skripsi/tambah", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "skripsi/tambah.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/user/profile", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "user/profile.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/verification-queue", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/verification_queue.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/skripsi/detail", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "skripsi/detail.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/users", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/users.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/user/dashboard", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "user/dashboard.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/skripsi/edit", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "skripsi/edit.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/theses", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/theses.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/faculties", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/faculties.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/departments", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/departments.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

app.get("/admin/reports", (req, res) => {
  let html = fs.readFileSync(path.join(process.cwd(), "admin/reports.php"), "utf-8");
  html = html.replace(/<\?php[\s\S]*?\?>/g, "");
  res.send(html);
});

// Assets bridge
app.use("/assets", express.static(path.join(process.cwd(), "assets")));
app.use(express.static(process.cwd()));

app.listen(PORT, "0.0.0.0", () => {
  console.log(`PREVIEW ACTIVE at http://localhost:${PORT}`);
  console.log(`NOTE: Versi Node.js telah dihapus. File root adalah MURNI PHP.`);
});
