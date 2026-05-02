var config = {
  //  baseApiUrl: 'https://fwd.senarios.co/api/cms/', // Production
  // baseApiUrl: 'https://fwd-dev.senarios.co/api/cms/', // Dev environment
  baseApiUrl: (typeof window !== 'undefined' && window.location)
    ? window.location.origin + '/api/cms/'   // Use same origin (works for 127.0.0.1, localhost, etc.)
    : '/api/cms/',                            // Fallback for SSR/build
    storage: localStorage,
    // timeOptions for reps (time-based exercises)
    timeOptions: [
        "5 sec", "10 sec", "15 sec", "20 sec", "25 sec", "30 sec", "35 sec", "40 sec",
        "45 sec", "50 sec", "55 sec", "60 sec", "90 sec", "2 min", "3 min", "4 min", "5 min", "10 min"
    ],
    // Rest period: 0-240 seconds. Each option is { value: seconds, label: "0 sec" | "2 min" | ... }
    restPeriodOptions: [
        { value: 0, label: "0 sec" },
        { value: 5, label: "5 sec" }, { value: 10, label: "10 sec" }, { value: 15, label: "15 sec" },
        { value: 20, label: "20 sec" }, { value: 25, label: "25 sec" }, { value: 30, label: "30 sec" },
        { value: 35, label: "35 sec" }, { value: 40, label: "40 sec" }, { value: 45, label: "45 sec" },
        { value: 50, label: "50 sec" }, { value: 55, label: "55 sec" }, { value: 60, label: "1 min" },
        { value: 90, label: "1 min 30 sec" }, { value: 120, label: "2 min" }, { value: 180, label: "3 min" },
        { value: 240, label: "4 min" }
    ],
    exerciseTarget:['chest','back','arms','abdominals','legs','shoulders','biceps','triceps','forearms'],
    exerciseTarget2 : [
        {id : 'chest',text: 'chest'},
        {id : 'back',text: 'back'},
        {id : 'arms',text: 'arms'},
        {id : 'abdominals',text: 'abdominals'},
        {id : 'legs',text: 'legs'},
        {id : 'shoulders',text: 'shoulders'},
        {id : 'biceps',text: 'biceps'},
        {id : 'triceps',text: 'triceps'},
        {id : 'forearms',text: 'forearms'},
    ],
    youtubeApiKey: 'AIzaSyBce9pKIUO63SY8YlAugLyBQ9Mye87tPDc'
}
export default config;
