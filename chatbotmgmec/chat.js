// Select DOM elements
const chatBody = document.querySelector(".chat-body");
const messageInput = document.querySelector(".message-input");
const sendMessageButton = document.querySelector("#send-message");
const chatbotToggler = document.querySelector("#chatbot-toggler");
const closeChatbot = document.querySelector("#close-chatbot");

// College information database
const collegeInfo = {
    general: {
        name: "Mahatma Gandhi Memorial Evening College",
        shortName: "MGMEC",
        founded: "2022",
        founder: "Sri T. Sathish U. Pai",
        sponsor: "Academy of General Education, Manipal",
        affiliation: "Mangalore University",
        location: "Udupi-Manipal Highway, 2km from Udupi city",
        principal: "Dr. Devidas S. Naik, M.A., Ph.D."
    },
    vision: "To impart the need-based, skill-integrated, cost-effective, quality, and holistic education to the working youth who wish to continue their education while earning.",
    mission: "To provide the youth with all possible opportunities of graduation and to enhance educational facility for those who work during the day for their livelihood and wish to continue higher education.",
    objectives: [
        "To provide an opportunity for higher education to educationally deprived students.",
        "To enhance educational facilities for those who work during the day for their livelihood and wish to continue higher education.",
        "To equip the students with the skills of learning, communication, self-study, and self-analysis.",
        "To enlighten students on different virtues and values of life, thereby creating morality and socially committed noble citizens.",
        "To render academic assistance to the socially, economically, and academically disadvantaged students of society."
    ],
    courses: [
        { name: "Bachelor of Computer Science (BCA)", details: "AICTE Approved" },
        { name: "Bachelor of Commerce (BCOM)", details: "" },
        { name: "Bachelor of Business Administration (BBA)", details: "AICTE Approved" }
    ],
    facilities: [
        "Smart-board Classrooms",
        "State-of-the-art Computer Labs",
        "Library & Information Centre",
        "Audio-Visual Room",
        "Auditorium & Open Theatre",
        "Cafeteria",
        "Cooperative Store & Reprographic Centre",
        "Playground & Indoor Stadium"
    ],
    specialFeatures: [
        "CA/CS Coaching by reputed institutes",
        "Skill Development Courses",
        "Guest Lectures/Seminars/Workshops",
        "In-house competitions",
        "Value Added Certificate Courses",
        "Best of Placements",
        "Industrial Visit/Tour",
        "State-of-the-art Infrastructure",
        "Qualified/Experienced Staff",
        "Reasonable/Affordable Fees"
    ],
    coreValues: [
        "Commitment to Excellence",
        "Value Education",
        "Digital Skills",
        "Personality Development",
        "Leadership Training",
        "Yakshagana and Stage Acting",
        "Individual Mentoring"
    ],
    management: [
        { name: "Sri T. Sathish U. Pai", position: "President" },
        { name: "Dr. Ranjan R. Pai", position: "Member" },
        { name: "Dr. H.S. Ballal", position: "Member" },
        { name: "Dr. (Gen) M.D. Venkatesh", position: "Member" },
        { name: "Dr. Narayana Sabhahith", position: "Member" },
        { name: "Sri CA B.P. Varadaraya Pai", position: "Member" },
        { name: "Dr. Devidas S. Naik", position: "Special Invitee" },
        { name: "Sri T. Ranga Pai", position: "Special Invitee" },
        { name: "Prof. Laxminarayana Karantha", position: "Member/Secretary" }
    ],
    timings: {
        weekdays: "Monday to Friday: 1 pm to 9 pm",
        saturday: "Saturday: 1 pm to 7 pm"
    },
    attendance: "As per the Rules and Regulations of the Mangalore University 75% of attendance is compulsory for writing the semester exams. If a student has less than 75% attendance he/she has to repeat the semester.",
    contactInfo: {
        address: "Mahatma Gandhi Memorial Evening College, Udupi - 576 102",
        phone: "0820-2001877"
    },
    coordinators: {
        bca: "Dr. M. Vishwanath Pai, B.E., CNE, M.Sc.(IT), M.Phil., Ph.D. \nEmail: mvishwanathpai@gmail.com \nCell: 7896541233",
        bcom_bba: "Dr. Mallika A. Shetty, M.Com., M.B.A., Ph.D. \nEmail: mallikashetty@gmail.com \nCell: 7896541233"
    },
    faq: [
        {
            question: "What are the courses offered?",
            answer: "We offer BCA (AICTE Approved), BCOM, and BBA (AICTE Approved) programs."
        },
        {
            question: "What are the college timings?",
            answer: "Monday to Friday: 1 pm to 9 pm, Saturday: 1 pm to 7 pm"
        },
        {
            question: "What is the attendance requirement?",
            answer: "75% attendance is compulsory for writing semester exams as per Mangalore University regulations."
        },
        {
            question: "Is hostel facility available?",
            answer: "Yes, female students can stay at the ladies hostel, 'Vadiraja Vidyarthi Nilaya'."
        },
        {
            question: "How can I apply for admission?",
            answer: "Please visit our college office with your academic documents to apply for admission. You can also contact the respective course coordinators for more information."
        },
        {
            question: "Is admission open now?",
            answer: "Yes! The admission is open now for the year 2025-26."
        }
    ]
};

// Initialize chat with a welcome message
document.addEventListener("DOMContentLoaded", () => {
    setTimeout(() => {
        const welcomeMessage = createMessageElement(`
            <svg class="bot-avatar" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 1024 1024">
                <path d="M738.3 287.6H285.7c-59 0-106.8 47.8-106.8 106.8v303.1c0 59 47.8 106.8 106.8 106.8h81.5v111.1c0 .7.8 1.1 1.4.7l166.9-110.6 41.8-.8h117.4l43.6-.4c59 0 106.8-47.8 106.8-106.8V394.5c0-59-47.8-106.9-106.8-106.9zM351.7 448.2c0-29.5 23.9-53.5 53.5-53.5s53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5-53.5-23.9-53.5-53.5zm157.9 267.1c-67.8 0-123.8-47.5-132.3-109h264.6c-8.6 61.5-64.5 109-132.3 109zm110-213.7c-29.5 0-53.5-23.9-53.5-53.5s23.9-53.5 53.5-53.5 53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5zM867.2 644.5V453.1h26.5c19.4 0 35.1 15.7 35.1 35.1v121.1c0 19.4-15.7 35.1-35.1 35.1h-26.5zM95.2 609.4V488.2c0-19.4 15.7-35.1 35.1-35.1h26.5v191.3h-26.5c-19.4 0-35.1-15.7-35.1-35.1zM561.5 149.6c0 23.4-15.6 43.3-36.9 49.7v44.9h-30v-44.9c-21.4-6.5-36.9-26.3-36.9-49.7 0-28.6 23.3-51.9 51.9-51.9s51.9 23.3 51.9 51.9z"></path>
            </svg>
            <div class="message-text">
                Welcome to MGM Evening College Assistant! I can help with information about our courses, facilities, admission process, and more. How may I assist you today?
            </div>`, "bot-message");
        chatBody.appendChild(welcomeMessage);
    }, 500);
});

// Array to store chat history
const chatHistory = [];

// Store the initial height of the message input
const initialInputHeight = messageInput.scrollHeight;

// Function to create a message element with dynamic classes
const createMessageElement = (content, ...classes) => {
    const div = document.createElement("div");
    div.classList.add("message", ...classes);
    div.innerHTML = content;
    return div;
};

// Function to generate response based on user query
const generateBotResponse = async (incomingMessageDiv, userMessage) => {
    const messageElement = incomingMessageDiv.querySelector(".message-text");
    
    // Process user query and generate response
    try {
        const response = await processQuery(userMessage);
        messageElement.innerHTML = response;
    } catch (error) {
        console.log(error);
        messageElement.innerText = "I'm sorry, I couldn't process your request. Please try again.";
        messageElement.style.color = "#ff0000";
    } finally {
        incomingMessageDiv.classList.remove("thinking");
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });
    }
};

// Natural language processing for user queries
const processQuery = async (query) => {
    // Convert query to lowercase for easier matching
    query = query.toLowerCase();
    
    // Check for different types of queries
    if (query.includes("course") || query.includes("degree") || query.includes("program") || query.includes("bca") || query.includes("bcom") || query.includes("bba")) {
        return generateCoursesResponse();
    } else if (query.includes("contact information") || query.includes("college address") || query.includes("location") || query.includes("reach")) {
        return `${collegeInfo.contactInfo.address}<br>We are located on the Udupi-Manipal Highway, two kilometers away from Udupi city.`;
    } else if (query.includes("timing") || query.includes("hour") || query.includes("schedule") || query.includes("when") || query.includes("time")) {
        return generateTimingsResponse();
    }else if (query.includes("hostel") ||query.includes("hostel facilities") || query.includes("accommodation") || query.includes("lodging") || query.includes("staying")) {
        return "The college provides hostel facilities for female students at 'Vadiraja Vidyarthi Nilaya'. Mrs. Latha Nayak, our Hostel Warden.";
    } else if (query.includes("principal") || query.includes("Principal details") || query.includes("Principal contact information")) {
        return "MGM Evening college Principal is Dr.Devidas S.Naik , M.A., Ph.D.<br>Cell:<br>9986112977 / 88619110590";
    } else if (query.includes("facility") || query.includes("facilities") || query.includes("infrastructure") || query.includes("amenities") || query.includes("lab") || query.includes("library")) {
        return generateFacilitiesResponse();
    } else if (query.includes("faculty") || query.includes("staff") || query.includes("teacher") || query.includes("professor")) {
        return "Our college has highly qualified and experienced faculty members dedicated to providing quality education. For specific faculty information, please visit our college office or check the college notice board.";
    } else if (query.includes("fee") || query.includes("cost") || query.includes("payment") || query.includes("tuition")) {
        return "For detailed fee structure, please contact our college office. We offer reasonable and affordable fees compared to other institutions in the region.";
    } else if (query.includes("admission") || query.includes("apply") || query.includes("enrollment") || query.includes("register")) {
        return "The admission for the year 2025-26 is open now. For admission inquiries, please visit our college office with your academic documents. You can also contact our course coordinators:<br><br> BCA:<br>Dr. M. Vishwanath Pai <br> Cell:  9008515307 / 9481643307<br><br> BCOM/BBA:<br> Dr. Mallika A. Shetty <br> Cell : 9448603135 / 866080746";
    }  else if (query.includes("about") || query.includes("history") || query.includes("background") || query.includes("college")) {
        return generateAboutResponse();
    } else if (query.includes("vision") || query.includes("objective") || query.includes("goal")) {
        return generateVisionMissionResponse();
    } else if (query.includes("scholarship") || query.includes("financial aid") || query.includes("funding")) {
        return "Scholarships are given to meritorious and deserving students by Sunidhi Foundation Trust, Manipal every year. The Manipal Academy of Higher Education (MAHE) also awards scholarships for deserving candidates.";
    } else if (query.includes("attendance") || query.includes("absent") || query.includes("presence")) {
        return `${collegeInfo.attendance}`;
    } else if (query.includes("nep") || query.includes("new education policy") || query.includes("education policy")) {
        return "The New Education Policy (NEP) has been implemented since 2021-22. Students can get a maximum of 40% credits through online courses, and have the option for dual degrees. For more details, please visit our college office.";
    } else if (query.includes("placement") || query.includes("job") || query.includes("career") || query.includes("employment")) {
        return "Our Placement Cell functions effectively by arranging Placement Drives regularly for final year students. This facility is extended to evening college students when they reach their final year.";
    } else if (query.includes("contact") || query.includes("contact details") || query.includes("address") || query.includes("location") || query.includes("phone") || query.includes("reach")) {
        return `${collegeInfo.contactInfo.address}<br>We are located on the Udupi-Manipal Highway, two kilometers away from Udupi city.<br>Phone number: ${collegeInfo.contactInfo.phone}`;
    } else if (query.includes("hello") || query.includes("hi") || query.includes("hey") || query.includes("greetings")) {
        return "Hello! Welcome to MGM Evening College Assistant. How can I help you today?";
    } else if (query.includes("thank")) {
        return "You're welcome! If you have any more questions, feel free to ask.";
    } else if (query.includes("bye") || query.includes("bii") || query.includes("goodbye")) {
        return "Thank you for chatting with MGM Evening College Assistant. Have a great day!";
    } else if (query.includes("coordinator")) {
        return "Course coordinators:<br><br> BCA:<br>Dr. M. Vishwanath Pai <br> Cell:  9008515307 / 9481643307<br><br> BCOM/BBA:<br> Dr. Mallika A. Shetty <br> Cell : 9448603135 / 866080746";
    } else {
        // Generic response for unrecognized queries
        return "I'm here to help with information about MGM Evening College. You can ask about our courses, facilities, admission process, faculty, fees, hostel facilities, placements, and more. How can I assist you?";
    }
};

// Generate responses for specific query types
const generateCoursesResponse = () => {
    let response = "<strong>Courses Offered at MGM Evening College:</strong><br><br>";
    collegeInfo.courses.forEach(course => {
        response += `• ${course.name} ${course.details ? `(${course.details})` : ""}<br>`;
    });
    response += "<br>For more details about these courses, please contact our college office or the respective course coordinators.";
    return response;
};

const generateTimingsResponse = () => {
    return `<strong>College Timings:</strong><br><br>${collegeInfo.timings.weekdays}<br>${collegeInfo.timings.saturday}`;
};

const generateFacilitiesResponse = () => {
    let response = "<strong>Facilities Available:</strong><br><br>";
    collegeInfo.facilities.forEach(facility => {
        response += `• ${facility}<br>`;
    });
    return response;
};

const generateAboutResponse = () => {
    return `<strong>About MGM Evening College:</strong><br><br>
    ${collegeInfo.general.name} (${collegeInfo.general.shortName}) was founded in ${collegeInfo.general.founded} by ${collegeInfo.general.founder}. It is sponsored by the ${collegeInfo.general.sponsor} and affiliated to ${collegeInfo.general.affiliation}.<br><br>
    The college is established mainly to cater to the needs of working professionals and students who aim to use their morning time by enhancing their skills or work part-time. It benefits a large number of students who could not pursue their degree due to poverty and are employed in the private sector.`;
};

const generateVisionMissionResponse = () => {
    let response = `<strong>Vision:</strong><br>${collegeInfo.vision}<br><br>`;
    response += `<strong>Mission:</strong><br>${collegeInfo.mission}<br><br>`;
    response += "<strong>Objectives:</strong><br>";
    collegeInfo.objectives.forEach((objective, index) => {
        response += `${index + 1}. ${objective}<br>`;
    });
    return response;
};

// Function to handle outgoing user message
const handleOutgoingMessage = (e) => {
    e.preventDefault();
    const userMessage = messageInput.value.trim();
    if (!userMessage) return;
    
    messageInput.value = "";
    messageInput.dispatchEvent(new Event("input"));

    // Create and display user message
    const messageContent = `<div class="message-text"></div>`;
    const outgoingMessageDiv = createMessageElement(messageContent, "user-message");
    outgoingMessageDiv.querySelector(".message-text").innerText = userMessage;

    chatBody.appendChild(outgoingMessageDiv);
    chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });

    // Simulate bot response with thinking indicator after a delay
    setTimeout(() => {
        const messageContent = `<svg class="bot-avatar" xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 1024 1024">
                    <path d="M738.3 287.6H285.7c-59 0-106.8 47.8-106.8 106.8v303.1c0 59 47.8 106.8 106.8 106.8h81.5v111.1c0 .7.8 1.1 1.4.7l166.9-110.6 41.8-.8h117.4l43.6-.4c59 0 106.8-47.8 106.8-106.8V394.5c0-59-47.8-106.9-106.8-106.9zM351.7 448.2c0-29.5 23.9-53.5 53.5-53.5s53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5-53.5-23.9-53.5-53.5zm157.9 267.1c-67.8 0-123.8-47.5-132.3-109h264.6c-8.6 61.5-64.5 109-132.3 109zm110-213.7c-29.5 0-53.5-23.9-53.5-53.5s23.9-53.5 53.5-53.5 53.5 23.9 53.5 53.5-23.9 53.5-53.5 53.5zM867.2 644.5V453.1h26.5c19.4 0 35.1 15.7 35.1 35.1v121.1c0 19.4-15.7 35.1-35.1 35.1h-26.5zM95.2 609.4V488.2c0-19.4 15.7-35.1 35.1-35.1h26.5v191.3h-26.5c-19.4 0-35.1-15.7-35.1-35.1zM561.5 149.6c0 23.4-15.6 43.3-36.9 49.7v44.9h-30v-44.9c-21.4-6.5-36.9-26.3-36.9-49.7 0-28.6 23.3-51.9 51.9-51.9s51.9 23.3 51.9 51.9z"></path>
                </svg>
                <div class="message-text">
                    <div class="thinking-indicator">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                </div>`;

        const incomingMessageDiv = createMessageElement(messageContent, "bot-message", "thinking");
        chatBody.appendChild(incomingMessageDiv);
        chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: "smooth" });

        generateBotResponse(incomingMessageDiv, userMessage);
    }, 600);
};

// Handle Enter key press for sending messages
messageInput.addEventListener("keydown", (e) => {
    const userMessage = e.target.value.trim();

    if (e.key === "Enter" && userMessage && !e.shiftKey && window.innerWidth > 768) {
        handleOutgoingMessage(e);
    }
});

// Adjust input height dynamically
messageInput.addEventListener("input", () => {
    messageInput.style.height = `${initialInputHeight}px`;
    messageInput.style.height = `${messageInput.scrollHeight}px`;
    document.querySelector(".chat-form").style.borderRadius = messageInput.scrollHeight > initialInputHeight ? "15px" : "32px";
});

// Event listeners for buttons
sendMessageButton.addEventListener("click", (e) => handleOutgoingMessage(e));
chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
closeChatbot.addEventListener("click", () => document.body.classList.remove("show-chatbot"));

// Initialize emoji picker and handle emoji selection
const picker = new EmojiMart.Picker({
    theme: "light", // Light theme for emoji picker
    skinTonePosition: "none", // Disable skin tone selection
    previewPosition: "none", // Disable preview section
    onEmojiSelect: (emoji) => {
        const { selectionStart: start, selectionEnd: end } = messageInput; // Get cursor position
        messageInput.setRangeText(emoji.native, start, end, "end"); // Insert emoji at cursor position
        messageInput.focus(); // Focus on the input field
    },
    onClickOutside: (e) => {
        if (e.target.id == "emoji-picker") {
            document.body.classList.toggle("show-emoji-picker"); // Toggle emoji picker visibility
        } else {
            document.body.classList.remove("show-emoji-picker"); // Hide emoji picker
        }
    }
});
document.querySelector(".chat-form").appendChild(picker); // Append emoji picker to chat form