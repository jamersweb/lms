# Sunnah LMS - Login Credentials & Quick Start

## 🔐 Login Credentials

### Admin Account
- **Email**: `admin@sunnah-lms.com`
- **Password**: `password`
- **Access**: Full admin privileges

### Student Accounts
1. **Student 1 (Umar)**
   - **Email**: `umar@example.com`
   - **Password**: `password`

2. **Student 2 (Fatima)**
   - **Email**: `fatima@example.com`
   - **Password**: `password`

---

## 🚀 Quick Start

### 1. Start the Servers
```bash
# Terminal 1: Laravel Server
php artisan serve
# Runs on: http://127.0.0.1:8000

# Terminal 2: Vite Dev Server  
npm run dev
# Runs on: http://localhost:5174
```

### 2. Access the Application
1. Open your browser and go to: **http://127.0.0.1:8000**
2. You'll be automatically redirected to the login page
3. Use one of the credentials above to log in
4. After login, you'll be redirected to the dashboard

---

## 📱 What You Can Test

### As Admin (`admin@sunnah-lms.com`)
- ✅ View all courses
- ✅ Enroll in courses
- ✅ Complete lessons and earn points
- ✅ Create and track habits
- ✅ Write journal entries
- ✅ Take notes on lessons
- ✅ Participate in discussions
- ✅ View leaderboard rankings
- ✅ Access admin features (if implemented)

### As Student (`umar@example.com` or `fatima@example.com`)
- ✅ All the same features as admin
- ✅ See your personal progress
- ✅ Track your habit streaks
- ✅ Compete on the leaderboard

---

## 🎯 Key Features to Try

### 1. Course Enrollment & Progress
1. Go to **Courses** page
2. Click on a course
3. Click **Enroll** button
4. Navigate to a lesson
5. Mark it as **Complete** (+10 points!)

### 2. Habit Tracking
1. Go to **Habits** page
2. Click **New Habit**
3. Create a habit (e.g., "Morning Adhkar")
4. Click **✓ Done** to log today's completion
5. Watch your streak grow!

### 3. Journal
1. Go to **Journal** page
2. Select your mood
3. Write today's reflection
4. Click **Save Entry**

### 4. Discussions
1. Go to a **Course** page
2. Click **Discussions** tab
3. Create a new discussion
4. Post replies (+2 points per reply!)

### 5. Leaderboard
1. Go to **Leaderboard** page
2. See your ranking
3. View top performers
4. Check your total points

---

## 🎮 Points System

| Action | Points Earned |
|--------|--------------|
| Complete a lesson | **+10 points** |
| Complete a course | **+50 points** (bonus) |
| Create a discussion | **+5 points** |
| Reply to discussion | **+2 points** |
| Log a habit | Ready to activate |
| Write journal entry | Ready to activate |

---

## 🐛 Troubleshooting

### "Page Not Found" or 404 Errors
- Make sure both servers are running (Laravel + Vite)
- Check that you're accessing `http://127.0.0.1:8000` (not localhost:5174)

### "Unauthenticated" Errors
- Clear your browser cache and cookies
- Try logging out and logging back in
- Make sure you're using the correct credentials

### Styling Looks Broken
- Ensure Vite dev server is running (`npm run dev`)
- Check browser console for any errors
- Try hard refresh (Ctrl+F5 or Cmd+Shift+R)

### Database Errors
- Run: `php artisan migrate:fresh --seed`
- This will reset the database and create fresh demo data

---

## 📊 Database Info

The demo database includes:
- **3 Users** (1 admin, 2 students)
- **3 Courses** with modules and lessons
- **Sample habits, journal entries, and notes**
- **Discussion threads and replies**
- **Points events and badges**

All data is seeded automatically when you run migrations.

---

## 🎨 Design System

**Colors:**
- **Primary (Emerald)**: `#059669` - Growth, Sunnah
- **Secondary (Gold)**: `#D97706` - Excellence, Ihsan
- **Neutral (Slate)**: Professional backgrounds

**Fonts:**
- **Headings**: Playfair Display (serif)
- **Body**: Inter (sans-serif)

---

## 🔧 Development Commands

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test --filter=HabitTest

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Rebuild frontend
npm run build

# Check routes
php artisan route:list
```

---

## 📝 Next Steps

1. **Test all features** using the credentials above
2. **Report any bugs** you find
3. **Suggest improvements** for UX
4. **Add more content** (courses, lessons, etc.)

Enjoy exploring the Sunnah LMS! 🚀
