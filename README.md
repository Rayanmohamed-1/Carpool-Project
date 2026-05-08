CARPOOL-PROJECT
This is a client-server web application that is designed to help university students share rides to and from campus. The system will allow users to post or join rides, view available options, and communicate with other users, while the server handles ride matching and data management.

The application uses a simple but effective tech stack. The frontend is built with HTML and CSS, while the backend runs on PHP using an Apache server. A MySQL database is used to store key data such as user accounts, rides, bookings, and messages. On top of that, Docker and Docker Compose are used to containerise the whole system thus making it easier to run consistently across different machines without setup issues.

Deployment is done locally using Docker. After cloning the repository, the project folder must be opened in a terminal where the Docker Compose file is run to build and start both the web server and database containers. Once the containers are running, the system can then be accessed through a browser using localhost:8080. The containers can also be stopped when needed through Docker.

passenger dashboard branch update

# Project Title
Carpooling Web Application

---

## Project Overview
This project is a client-server web application designed to help university students share rides to and from campus. Users can search for available rides, join or book rides, and communicate with other users. The system is designed to make travel more affordable, efficient, and social for students.

The application uses a web-based interface connected to a backend server and database for managing ride data and bookings.

---

## Technologies Used

### Frontend
- HTML
- CSS
- JavaScript
- Bootstrap

### Backend
- PHP (Apache Server)

### Database
- MySQL

### Tools
- Docker
- Docker Compose
- OpenStreetMap (Leaflet.js)

---

## Features
- User login system (if applicable)
- Passenger dashboard
- Search rides by pickup and dropoff location
- View available rides
- Ride booking system
- Ride details modal popup
- Chat/message section (basic)
- OpenStreetMap integration
- City autocomplete search
- Responsive design

---

## Requirements
Before running the project, make sure you have:

- Docker Desktop installed
- Docker Compose installed
- Git installed
- Web browser (Chrome / Edge / Firefox)

---

## Installation / Running the Project

### 1. Clone the repository
```bash
git clone <repository-link>

### 2. Open the project folder
cd Carpool-Project

###3. Start Dokcer containers
docker compose up --build

###4. Open the application in browser
http://localhost:8080/passengerDashboard.php


## System Functions

The system is divided into two main user roles: **Driver** and **Passenger**, each with their own functionality.

### Authentication System
- User registration for new accounts (drivers and passengers)
- Login system for existing users
- Session management to keep users logged in securely
- Logout functionality

### Driver Functions
- Driver registration and profile creation
- Ability to post available rides
- Manage (view/edit/delete) posted rides
- View passenger bookings for their rides

### Passenger Functions
- Passenger dashboard to search and view available rides
- Search rides using pickup and dropoff locations
- View ride details in a modal popup
- Book available rides using booking form
- Basic chat/message interaction with driver (if implemented)

### Ride Management System
- Rides are stored in a MySQL database
- SQL queries dynamically filter ride results based on user search
- Ride information includes location, date, time, price, and seats

### Map Integration
- OpenStreetMap (Leaflet.js) is used to display ride-related locations
- Helps users visually understand ride areas


Carpool-Project/
│
├── docker-compose.yml
├── schema.sql
│
├── server/
│   ├── passengerDashboard.php
│   ├── passengerbooking.php
│   ├── setup.php
│   ├── style.css


## Team Contributions

- Maryam – Login pages and home pages  
- Rayan – Driver registration and driver dashboard  
- Sachit – Testing and system validation  
- Janice Ambati – Passenger dashboard, ride search system, booking system, and map integration


## Future Improvements

In future versions of the system, several enhancements could be implemented to improve functionality and user experience:

- Real-time chat system between users for better communication  
- Full driver dashboard with ride management features  
- User profile management system (edit/view personal details)  
- Live GPS tracking for rides in real time  
- Payment system integration for booking confirmation and transactions  
- Improved security measures, including stronger validation and authentication  
- Enhanced UI/UX design for a more modern and user-friendly interface
