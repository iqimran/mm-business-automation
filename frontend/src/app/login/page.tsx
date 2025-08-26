"use client";
import { useState } from "react";
import { useRouter } from "next/navigation";
import { apiService } from "@/services/api";

export default function LoginPage() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [msg, setMsg] = useState("");
  const [errorMessage, setErrorMessage] = useState("");
  const [successMessage, setSuccessMessage] = useState("");
  const router = useRouter();

  async function onSubmit(e: React.FormEvent) {
    e.preventDefault();
    setErrorMessage("");
    setSuccessMessage("");
    try {
      const response = await apiService.login({ email, password });
      setSuccessMessage("Login successful!");
      setErrorMessage("");
      const user = await apiService.me();
      console.log(user);
      //setMsg(`Welcome ${user.data.name}`);
      
      // redirect to dashboard
      router.push("/dashboard");
    } catch (error: any) {
      console.log(error);
      setErrorMessage(error?.response?.data?.error ?? "Login failed");
    }
  }

  return (
     <div className="min-h-screen grid place-items-center p-6">
      <form autoComplete="off" onSubmit={onSubmit} className="w-full max-w-sm space-y-3 border rounded p-6">
        <h1 className="text-xl font-semibold">Sign in</h1>
        <input
          className="border rounded w-full p-2"
          placeholder="Email"
          type="email"
          value={email}
          onChange={(e)=>setEmail(e.target.value)}
        />
        <input
          className="border rounded w-full p-2"
          placeholder="Password"
          type="password"
          value={password}
          onChange={(e)=>setPassword(e.target.value)}
        />
        <button className="w-full bg-black text-white rounded p-2">Login</button>
        {errorMessage && <p className="text-red-500">{errorMessage}</p>}
        {successMessage && <p className="text-green-500">{successMessage}</p>}
        {msg && <p className="text-sm text-red-600">{msg}</p>}
      </form>
    </div>
  );
}