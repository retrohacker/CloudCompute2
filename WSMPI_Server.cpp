/*
 * WSMPI_Server.cpp
 *
 *  Created on: Jun 24, 2012
 *      Author: crackers
 */

#include "WSMPI_Server.h"

WSMPI_Server::WSMPI_Server(char* address, int port) {
	//TODO verify the data passed in
	MasterSocketInfo.sin_family = AF_INET;
	MasterSocketInfo.sin_port = htons(port);
	inet_pton(AF_INET, address, &MasterSocketInfo.sin_addr);

	//Zero out the rest of the structure
	for(int i=0;i<8;i++) {
		MasterSocketInfo.sin_zero[i] = 0;
	}
}

WSMPI_Server::~WSMPI_Server() {
	// TODO Ensure memory is deallocated upon going out of scope
}

int WSMPI_Server::start() {
	//TODO add error checking and handling
	MasterSocket = socket(AF_INET, SOCK_STREAM, 0);
	char buf[INET_ADDRSTRLEN];
	inet_ntop(AF_INET, &MasterSocketInfo.sin_addr, buf, INET_ADDRSTRLEN);
	std::cout << "Starting server at " << buf <<
			" on port " << ntohs(MasterSocketInfo.sin_port) << ": ";
	int b = bind(MasterSocket, (sockaddr*)&MasterSocketInfo, sizeof(sockaddr));
	int li = listen(MasterSocket,5);
	if(b == -1 || li == -1) {
		std::cout << strerror( errno ) << "\n";
		return -1;
	} else {
		std::cout << "Success!\n";
		return 0;
	}
}
