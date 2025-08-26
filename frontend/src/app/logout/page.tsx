"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { apiService, setAccessToken } from "@/services/api";

export default function LogoutPage() {
  const router = useRouter();

  useEffect(() => {
    const doLogout = async () => {
      try {
        await apiService.logout(); // backend logout
      } catch (error) {
        console.error("Logout error:", error);
      } finally {
        // ✅ Always clear token & redirect
        setAccessToken(null);
        router.push("/login");
      }
    };

    doLogout();
  }, [router]);

  return (
    <div className="flex items-center justify-center h-screen">
      <p className="text-gray-600 text-lg">Logging out...</p>
    </div>
  );
}
