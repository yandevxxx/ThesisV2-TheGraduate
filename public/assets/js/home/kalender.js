$(document).ready(function () {
  // 1. Cek dulu: Apakah di halaman ini ada elemen ID 'calendar'?
  const calendarEl = document.getElementById("calendar");

  // Jika TIDAK ADA kalender (misal di halaman Edit), STOP DISINI. Jangan lanjut.
  if (!calendarEl) {
      return; 
  }

  // --- KODE KEBAWAH INI BARU JALAN KALAU KALENDER ADA ---
  const events = [];

  $("#tableKegiatan tr").each(function () {
    let namaKegiatan = $(this).children(".nama-kegiatan").text().trim();
    let tanggalKegiatan = $(this).children(".tanggal-kegiatan").text().trim();
    let lockStatus = $(this).data("lock-status") || "";

    if(namaKegiatan && tanggalKegiatan) { // Tambah cek biar aman
        let red = Math.floor(Math.random() * 256);
        let green = Math.floor(Math.random() * 256);
        let blue = Math.floor(Math.random() * 256);
        events.push({
        title: lockStatus === "locked" ? "🔒 " + namaKegiatan : namaKegiatan,
        start: tanggalKegiatan.split("_")[0],
        end: moment(tanggalKegiatan.split("_")[1])
        .add(1, "days")
        .format("YYYY-MM-DD"),
        backgroundColor: lockStatus === "locked" ? "#e9ecef" : `rgba(${red},${green},${blue},0.2)`,
        textColor: lockStatus === "locked" ? "#495057" : `rgba(${red},${green},${blue},1)`,
        borderColor: lockStatus === "locked" ? "#495057" : `rgba(${red},${green},${blue},1)`,
    });
    }
  });

  const calender = new FullCalendar.Calendar(calendarEl, {
    initialView: "dayGridMonth",
    contentHeight: "auto",
    events: events,
  });
  calender.render();
});