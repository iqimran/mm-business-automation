"use client";
import { useEffect, useState } from "react";
import { apiService } from "@/services/api";

export default function Dashboard() {
  const [user, setUser] = useState<any>(null);
  const [error, setError] = useState("");

  useEffect(() => {
    apiService.me()
      .then(setUser)
      .catch(() => setError("Please log in"));
  },[]);

  if (error) return <div className="p-6">{error}</div>;
  if (!user) return <div className="p-6">Loading…</div>;
  return (
    <div className="p-6">
      <h1 className="text-2xl">Hello, {user.name}</h1>
      <p>Welcome to MM Business</p>
    </div>
  );
}
