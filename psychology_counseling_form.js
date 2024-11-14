document.addEventListener("DOMContentLoaded", function() {
    const data = {
        Iba_Main: {
            CABA: ["Bachelor of Science in Accountancy", "Bachelor of Science in Accounting and Information System", "Bachelor of Science in Business Administration", "Bachelor of Public Administration"],
            CAS: ["Bachelor of Science in Biology", "Bachelor of Science in Psychology"],
            CCIT: ["Bachelor of Science in Information Technology", "Bachelor of Science in Computer Science"],
            CTE: ["Bachelor of Secondary Education", "Bachelor of Elementary Education", "Bachelor of Physical Education", "Certificate of Professional Education"],
            COE: ["Civil Engineering", "Computer Engineering", "Mechanical Engineering", "Electrical Engineering"],
            CIT: ["Bachelor of Technology and Livelihood Education", "Bachelor of Technical Vocational Teachers Education", "Bachelor of Science in Industrial Technology"],
            CAF: ["Bachelor of Science in Environmental Science"],
            CON: ["Bachelor of Science in Nursing"],
            CTHM: ["Bachelor of Science in Hospitality Management", "Bachelor of Science in Tourism Management"]
        },
        // Example of a campus with no colleges, just courses
        Castillejos: {
            NONE: ["Bachelor of Science in Business Administration", "Bachelor of Secondary Education", "Bachelor of Elementary Education", "Certificate of Professional Education", "Bachelor of Science in Computer Science"]
        },
        San_Marcelino: {
            NONE: ["Bachelor of Secondary Education", "Bachelor of Elementary Education", "Bachelor of Science in Agriculture", "Bachelor of Agricultural Technology", "Bachelor of Science in Computer Science", "Bachelor of Science in Hospitality Management"]
        },
        Botolan: {
            NONE: ["Bachelor of Elementary Education", "Bachelor of Science in Agriculture - Crop Science", "Bachelor of Science in Agriculture - Animal Science", "Bachelor of Science in Forestry"]
        },
        Masinloc: {
            NONE: ["Bachelor of Science in Business Administration", "Bachelor of Elementary Education", "Bachelor of Science in Information Technology", "Certificate of Professional Education"]
        },
        Candelaria: {
            NONE: ["Bachelor of Science in Fisheries", "Bachelor of Science in Information Technology"]
        },
        Sta_Cruz: {
            NONE: ["Bachelor of Elementary Education", "Bachelor of Secondary Education", "Bachelor of Science in Computer Science"]
        }
    };

    // Event listener for campus selection
    document.getElementById("campus").addEventListener("change", function() {
        const selectedCampus = this.value;
        const collegeSelect = document.getElementById("college");
        const courseSelect = document.getElementById("course");
    
        // Clear previous options
        collegeSelect.innerHTML = '<option value="">Select College</option>';
        courseSelect.innerHTML = '<option value="">Select Course</option>';
    
        // Populate the college dropdown if colleges exist for the selected campus
        if (data[selectedCampus]) {
            // If the campus has courses, populate them directly
            if (data[selectedCampus].courses) {
                data[selectedCampus].courses.forEach(function(course) {
                    const option = document.createElement("option");
                    option.value = course;
                    option.text = course;
                    courseSelect.appendChild(option);
                });
            }

            // Populate the college dropdown if colleges exist
            Object.keys(data[selectedCampus]).forEach(function(college) {
                if (college !== 'courses') { // Check if it's not the courses key
                    const option = document.createElement("option");
                    option.value = college;
                    option.text = college;
                    collegeSelect.appendChild(option);
                }
            });
        }
    });

    // Event listener for college selection
    document.getElementById("college").addEventListener("change", function() {
        const selectedCampus = document.getElementById("campus").value;
        const selectedCollege = this.value;
        const courseSelect = document.getElementById("course");

        // Clear previous course options
        courseSelect.innerHTML = '<option value="">Select Course</option>';

        // Populate the course dropdown based on selected college
        if (data[selectedCampus] && data[selectedCampus][selectedCollege]) {
            data[selectedCampus][selectedCollege].forEach(function(course) {
                const option = document.createElement("option");
                option.value = course;
                option.text = course;
                courseSelect.appendChild(option);
            });
        }
    });

    // Form submission validation
    document.querySelector('form').addEventListener('submit', function(event) {
        const collegeSelect = document.getElementById("college");
        const selectedCampus = document.getElementById("campus").value;

        // Check if college is required and not selected
        if (collegeSelect.value === "") {
            alert("Please select a college.");
            event.preventDefault(); // Prevent form submission
        }
    });
});
