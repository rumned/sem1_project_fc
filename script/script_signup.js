document.addEventListener("DOMContentLoaded", () => {
  const role = document.getElementById("role");
  const studentFields = document.getElementById("student-fields");
  const lecturerFields = document.getElementById("lecturer-fields");

  function updateFields() {
    studentFields.hidden = true;
    lecturerFields.hidden = true;

    if (role.value === "student") {
      studentFields.hidden = false;
    } else if (role.value === "lecturer") {
      lecturerFields.hidden = false;
    }
  }

  updateFields(); // run on page load
  role.addEventListener("change", updateFields);
});
